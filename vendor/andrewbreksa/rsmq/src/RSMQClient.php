<?php
declare(strict_types=1);

namespace AndrewBreksa\RSMQ;

use AndrewBreksa\RSMQ\Exceptions\MessageToLongException;
use AndrewBreksa\RSMQ\Exceptions\QueueAlreadyExistsException;
use AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException;
use AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException;
use Predis\ClientInterface;

/**
 * Class RSMQClient
 *
 * @package AndrewBreksa\RSMQ
 * @author  Andrew Breksa <andrew@andrewbreksa.com>
 * @author  emre can islambey <eislambey@gmail.com>
 */
class RSMQClient implements RSMQClientInterface
{
    const MAX_DELAY        = 9999999;
    const MIN_MESSAGE_SIZE = 1024;
    const MAX_PAYLOAD_SIZE = 65536;

    /**
     * @var ClientInterface
     */
    private $predis;

    /**
     * @var string
     */
    private $ns;

    /**
     * @var bool
     */
    private $realtime;

    /**
     * @var string
     */
    private $receiveMessageSha1;

    /**
     * @var string
     */
    private $popMessageSha1;

    /**
     * @var string
     */
    private $changeMessageVisibilitySha1;

    /**
     * RSMQ constructor.
     *
     * @param ClientInterface $predis
     * @param string          $ns
     * @param bool            $realtime
     */
    public function __construct(ClientInterface $predis, string $ns = 'rsmq', bool $realtime = false)
    {
        $this->predis   = $predis;
        $this->ns       = "$ns:";
        $this->realtime = $realtime;

        $this->initScripts();
    }


    private function initScripts(): void
    {
        $receiveMessageScript = 'local msg = redis.call("ZRANGEBYSCORE", KEYS[1], "-inf", KEYS[2], "LIMIT", "0", "1")
			if #msg == 0 then
				return {}
			end
			redis.call("ZADD", KEYS[1], KEYS[3], msg[1])
			redis.call("HINCRBY", KEYS[1] .. ":Q", "totalrecv", 1)
			local mbody = redis.call("HGET", KEYS[1] .. ":Q", msg[1])
			local rc = redis.call("HINCRBY", KEYS[1] .. ":Q", msg[1] .. ":rc", 1)
			local o = {msg[1], mbody, rc}
			if rc==1 then
				redis.call("hset", KEYS[1] .. ":Q", msg[1] .. ":fr", KEYS[2])
				table.insert(o, KEYS[2])
			else
				local fr = redis.call("HGET", KEYS[1] .. ":Q", msg[1] .. ":fr")
				table.insert(o, fr)
			end
			return o';

        $popMessageScript = 'local msg = redis.call("ZRANGEBYSCORE", KEYS[1], "-inf", KEYS[2], "LIMIT", "0", "1")
			if #msg == 0 then
				return {}
			end
			redis.call("HINCRBY", KEYS[1] .. ":Q", "totalrecv", 1)
			local mbody = redis.call("HGET", KEYS[1] .. ":Q", msg[1])
			local rc = redis.call("HINCRBY", KEYS[1] .. ":Q", msg[1] .. ":rc", 1)
			local o = {msg[1], mbody, rc}
			if rc==1 then
				table.insert(o, KEYS[2])
			else
				local fr = redis.call("HGET", KEYS[1] .. ":Q", msg[1] .. ":fr")
				table.insert(o, fr)
			end
			redis.call("zrem", KEYS[1], msg[1])
			redis.call("hdel", KEYS[1] .. ":Q", msg[1], msg[1] .. ":rc", msg[1] .. ":fr")
			return o';

        $changeMessageVisibilityScript = 'local msg = redis.call("ZSCORE", KEYS[1], KEYS[2])
			if not msg then
				return 0
			end
			redis.call("ZADD", KEYS[1], KEYS[3], KEYS[2])
			return 1';

        $this->receiveMessageSha1          = $this->predis->script('load', $receiveMessageScript);
        $this->popMessageSha1              = $this->predis->script('load', $popMessageScript);
        $this->changeMessageVisibilitySha1 = $this->predis->script('load', $changeMessageVisibilityScript);
    }

    /**
     * @param string $name
     * @param int    $vt
     * @param int    $delay
     * @param int    $maxSize
     * @return bool
     * @throws QueueAlreadyExistsException
     */
    public function createQueue(string $name, int $vt = 30, int $delay = 0, int $maxSize = 65536): bool
    {
        $this->validate(
            [
                'queue'   => $name,
                'vt'      => $vt,
                'delay'   => $delay,
                'maxsize' => $maxSize,
            ]
        );

        $key = "{$this->ns}$name:Q";

        $resp = $this->predis->time();
        $this->predis->multi();
        $this->predis->hsetnx($key, 'vt', (string)$vt);
        $this->predis->hsetnx($key, 'delay', (string)$delay);
        $this->predis->hsetnx($key, 'maxsize', (string)$maxSize);
        $this->predis->hsetnx($key, 'created', $resp[0]);
        $this->predis->hsetnx($key, 'modified', $resp[0]);
        $resp = $this->predis->exec();

        if (!$resp[0]) {
            throw new QueueAlreadyExistsException('Queue already exists.');
        }

        return (bool)$this->predis->sadd("{$this->ns}QUEUES", [$name]);
    }

    /**
     * @param array<string, mixed> $params
     * @throws QueueParametersValidationException
     */
    public function validate(array $params): void
    {
        if (isset($params['queue']) && !preg_match('/^([a-zA-Z0-9_-]){1,160}$/', $params['queue'])) {
            throw new QueueParametersValidationException('Invalid queue name');
        }

        if (isset($params['id']) && !preg_match('/^([a-zA-Z0-9:]){32}$/', $params['id'])) {
            throw new QueueParametersValidationException('Invalid message id');
        }

        if (isset($params['vt']) && ($params['vt'] < 0 || $params['vt'] > self::MAX_DELAY)) {
            throw new QueueParametersValidationException('Visibility time must be between 0 and ' . self::MAX_DELAY);
        }

        if (isset($params['delay']) && ($params['delay'] < 0 || $params['delay'] > self::MAX_DELAY)) {
            throw new QueueParametersValidationException('Delay must be between 0 and ' . self::MAX_DELAY);
        }

        if (isset($params['maxsize'])
            && $params['maxsize'] !== -1 && ($params['maxsize'] < self::MIN_MESSAGE_SIZE || $params['maxsize'] > self::MAX_PAYLOAD_SIZE)
        ) {
            $message = "Maximum message size must be between %d and %d";
            throw new QueueParametersValidationException(sprintf($message, self::MIN_MESSAGE_SIZE,
                                                                 self::MAX_PAYLOAD_SIZE));
        }
    }

    /**
     * @return array<string>
     */
    public function listQueues(): array
    {
        return $this->predis->smembers("{$this->ns}QUEUES");
    }

    /**
     * @param string $name
     * @throws QueueNotFoundException
     */
    public function deleteQueue(string $name): void
    {
        $this->validate(
            [
                'queue' => $name,
            ]
        );

        $key = "{$this->ns}$name";
        $this->predis->multi();
        $this->predis->del(["$key:Q", $key]);
        $this->predis->srem("{$this->ns}QUEUES", $name);
        $resp = $this->predis->exec();

        if (!$resp[0]) {
            throw new QueueNotFoundException('Queue not found.');
        }
    }

    /**
     * @param string   $queue
     * @param int|null $vt
     * @param int|null $delay
     * @param int|null $maxSize
     * @return QueueAttributes
     * @throws QueueParametersValidationException
     * @throws QueueNotFoundException
     */
    public function setQueueAttributes(
        string $queue,
        int    $vt = null,
        int    $delay = null,
        int    $maxSize = null
    ): QueueAttributes {
        $this->validate(
            [
                'vt'      => $vt,
                'delay'   => $delay,
                'maxsize' => $maxSize,
            ]
        );
        $this->getQueue($queue);

        $time = $this->predis->time();
        $this->predis->multi();

        $this->predis->hset("{$this->ns}$queue:Q", 'modified', $time[0]);
        if ($vt !== null) {
            $this->predis->hset("{$this->ns}$queue:Q", 'vt', (string)$vt);
        }

        if ($delay !== null) {
            $this->predis->hset("{$this->ns}$queue:Q", 'delay', (string)$delay);
        }

        if ($maxSize !== null) {
            $this->predis->hset("{$this->ns}$queue:Q", 'maxsize', (string)$maxSize);
        }

        $this->predis->exec();

        return $this->getQueueAttributes($queue);
    }

    /**
     * @param string $name
     * @param bool   $generateUid
     * @return array|int[]
     * @throws QueueNotFoundException
     */
    private function getQueue(string $name, bool $generateUid = false): array
    {
        $this->validate(
            [
                'queue' => $name,
            ]
        );

        $transaction = $this->predis->transaction();
        $transaction->hmget("{$this->ns}$name:Q", ['vt', 'delay', 'maxsize']);
        $transaction->time();
        $resp = $transaction->execute();

        if (!isset($resp[0][0])) {
            throw new QueueNotFoundException('Queue not found.');
        }

        $ms = formatZeroPad((int)$resp[1][1], 6);


        $queue = [
            'vt'      => (int)$resp[0][0],
            'delay'   => (int)$resp[0][1],
            'maxsize' => (int)$resp[0][2],
            'ts'      => (int)($resp[1][0] . substr($ms, 0, 3)),
        ];

        if ($generateUid) {
            $queue['uid'] = base_convert(($resp[1][0] . $ms), 10, 36) . makeID(22);
        }

        return $queue;
    }

    /**
     * @param string $queue
     * @return QueueAttributes
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function getQueueAttributes(string $queue): QueueAttributes
    {
        $this->validate(
            [
                'queue' => $queue,
            ]
        );

        $key  = "{$this->ns}$queue";
        $resp = $this->predis->time();

        $transaction = $this->predis->transaction();
        $transaction->hmget("$key:Q", ['vt', 'delay', 'maxsize', 'totalrecv', 'totalsent', 'created', 'modified']);
        $transaction->zcard($key);
        $transaction->zcount($key, $resp[0] . '0000', "+inf");
        $transaction->hgetall("$key:Q");
        $resp = $transaction->execute();

        if($resp[1] != 0){
            foreach($resp[3] as $k => $v){
                if(substr($k, -4) == '_mat'){
                    $arr[] = [$k => $v];
                }
            }

            $rev = array_reverse($arr);
            for($i = $resp[1] - 1; $i >= 0; $i--){
                $mes[] = $rev[$i];
            }
        }

        if (!isset($resp[0][0])) {
            throw new QueueNotFoundException('Queue not found.');
        }

        return new QueueAttributes(
            (int)$resp[0][0],
            (int)$resp[0][1],
            (int)$resp[0][2],
            (int)$resp[0][3],
            (int)$resp[0][4],
            (int)$resp[0][5],
            (int)$resp[0][6],
            $resp[1],
            $resp[2],
            $mes
        );
    }

    /**
     * @param string   $queue
     * @param string   $message
     * @param int|null $delay
     * @return string
     * @throws MessageToLongException
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function sendMessage(string $queue, string $message, int $delay = null): string
    {
        $this->validate(
            [
                'queue' => $queue,
            ]
        );

        $q = $this->getQueue($queue, true);
        if ($delay === null) {
            $delay = $q['delay'];
        }

        if ($q['maxsize'] !== -1 && mb_strlen($message) > $q['maxsize']) {
            throw new MessageToLongException('Message too long');
        }

        $key = "{$this->ns}$queue";

        $this->predis->multi();
        $this->predis->zadd($key, [$q['uid']. '_mat' => $q['ts'] + $delay * 1000]);
        $this->predis->hset("$key:Q", $q['uid']. '_mat', $message);
        $this->predis->hincrby("$key:Q", 'totalsent', 1);

        if ($this->realtime) {
            $this->predis->zcard($key);
        }

        $resp = $this->predis->exec();

        if ($this->realtime) {
            $this->predis->publish("{$this->ns}rt:$$queue", $resp[3]);
        }

        return $q['uid']. '_mat';
    }

    /**
     * @param string $queue
     * @param array  $options
     * @return Message|null
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function receiveMessage(string $queue, array $options = []): ?Message
    {
        $this->validate(
            [
                'queue' => $queue,
            ]
        );

        $q  = $this->getQueue($queue);
        $vt = $options['vt'] ?? $q['vt'];

        $resp = $this->predis->evalsha(
            $this->receiveMessageSha1,
            3,
            "{$this->ns}$queue", $q['ts'],
            $q['ts'] + $vt * 1000
        );
        if (empty($resp)) {
            return null;
        }

        return new Message(
            $resp[0],
            $resp[1],
            (int)$resp[2],
            (int)$resp[3],
            base_convert(substr($resp[0], 0, 10), 36, 10) / 1000
        );
    }

    /**
     * @param string $queue
     * @return Message|null
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function popMessage(string $queue): ?Message
    {
        $this->validate(
            [
                'queue' => $queue,
            ]
        );

        $q = $this->getQueue($queue);

        $resp = $this->predis->evalsha($this->popMessageSha1, 2, "{$this->ns}$queue", $q['ts']);
        if (empty($resp)) {
            return null;
        }
        return new Message(
            $resp[0],
            $resp[1],
            (int)$resp[2],
            (int)$resp[3],
            base_convert(substr($resp[0], 0, 10), 36, 10) / 1000
        );
    }

    /**
     * @param string $queue
     * @param string $id
     * @return bool
     * @throws QueueParametersValidationException
     */
    public function deleteMessage(string $queue, string $id): bool
    {
        $this->validate(
            [
                'queue' => $queue,
                'id'    => $id,
            ]
        );

        $key = "{$this->ns}$queue";
        $this->predis->multi();
        $this->predis->zrem($key, $id);
        $this->predis->hdel("$key:Q", [$id, "$id:rc", "$id:fr"]);
        $resp = $this->predis->exec();

        return $resp[0] === 1 && $resp[1] > 0;
    }

    /**
     * @param string $queue
     * @param string $id
     * @param int    $vt
     * @return bool
     * @throws QueueParametersValidationException
     * @throws QueueNotFoundException
     */
    public function changeMessageVisibility(string $queue, string $id, int $vt): bool
    {
        $this->validate(
            [
                'queue' => $queue,
                'id'    => $id,
                'vt'    => $vt,
            ]
        );

        $q = $this->getQueue($queue, true);

        $resp = $this->predis->evalsha(
            $this->changeMessageVisibilitySha1,
            3,
            "{$this->ns}$queue",
            $id,
            $q['ts'] + $vt * 1000
        );

        return (bool)$resp;
    }

    public function displayMessage(string $queue): array
    {
        $this->validate(
            [
                'queue' => $queue,
            ]
        );

        $key  = "{$this->ns}$queue";
        $resp = $this->predis->time();

        $transaction = $this->predis->transaction();
        $transaction->hmget("$key:Q", ['vt', 'delay', 'maxsize', 'totalrecv', 'totalsent', 'created', 'modified']);
        $transaction->zcard($key);
        $transaction->zcount($key, $resp[0] . '0000', "+inf");
        $transaction->hgetall("$key:Q");
        $resp = $transaction->execute();

        if($resp[1] != 0){
            foreach($resp[3] as $k => $v){
                if(substr($k, -4) == '_mat'){
                    $arr[] = [$k => $v];
                }
            }

            $rev = array_reverse($arr);
            for($i = $resp[1] - 1; $i >= 0; $i--){
                $mes[] = $rev[$i];
            }
        }

        if (!isset($resp[0][0])) {
            throw new QueueNotFoundException('Queue not found.');
        }

        return $mes;
    }
}
