<?php declare(strict_types=1);


namespace AndrewBreksa\RSMQ;


use AndrewBreksa\RSMQ\Exceptions\MessageToLongException;
use AndrewBreksa\RSMQ\Exceptions\QueueAlreadyExistsException;
use AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException;
use AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException;

/**
 * Interface RSMQClientInterface
 *
 * @package AndrewBreksa\RSMQ
 * @author  Andrew Breksa <andrew@andrewbreksa.com>
 */
interface RSMQClientInterface
{

    /**
     * @param string $name
     * @param int    $vt
     * @param int    $delay
     * @param int    $maxSize
     * @return bool
     * @throws QueueAlreadyExistsException
     */
    public function createQueue(string $name, int $vt = 30, int $delay = 0, int $maxSize = 65536): bool;

    /**
     * @param string $queue
     * @param array  $options
     * @return Message|null
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function receiveMessage(string $queue, array $options = []): ?Message;

    /**
     * @param string $queue
     * @param string $id
     * @param int    $vt
     * @return bool
     * @throws QueueParametersValidationException
     * @throws QueueNotFoundException
     */
    public function changeMessageVisibility(string $queue, string $id, int $vt): bool;

    /**
     * @param string $queue
     * @return QueueAttributes
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function getQueueAttributes(string $queue): QueueAttributes;

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
    ): QueueAttributes;

    /**
     * @param string $name
     * @throws QueueNotFoundException
     */
    public function deleteQueue(string $name): void;

    /**
     * @param string $queue
     * @return Message|null
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function popMessage(string $queue): ?Message;

    /**
     * @param array<string, mixed> $params
     * @throws QueueParametersValidationException
     */
    public function validate(array $params): void;

    /**
     * @param string $queue
     * @param string $id
     * @return bool
     * @throws QueueParametersValidationException
     */
    public function deleteMessage(string $queue, string $id): bool;

    /**
     * @return array<string>
     */
    public function listQueues(): array;

    /**
     * @param string   $queue
     * @param string   $message
     * @param int|null $delay
     * @return string
     * @throws MessageToLongException
     * @throws QueueNotFoundException
     * @throws QueueParametersValidationException
     */
    public function sendMessage(string $queue, string $message, int $delay = null): string;

}