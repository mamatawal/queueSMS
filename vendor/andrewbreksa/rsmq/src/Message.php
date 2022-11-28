<?php declare(strict_types=1);


namespace AndrewBreksa\RSMQ;

/**
 * Class Message
 *
 * @package AndrewBreksa\RSMQ
 * @author  Andrew Breksa <andrew@andrewbreksa.com>
 */
class Message
{

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $receiveCount;

    /**
     * @var int
     */
    protected $firstReceived;

    /**
     * @var float
     */
    protected $sent;

    /**
     * Message constructor.
     *
     * @param string $id
     * @param string $message
     * @param int    $receiveCount
     * @param int    $firstReceived
     * @param float  $sent
     */
    public function __construct(string $id, string $message, int $receiveCount, int $firstReceived, float $sent)
    {
        $this->id            = $id;
        $this->message       = $message;
        $this->receiveCount  = $receiveCount;
        $this->firstReceived = $firstReceived;
        $this->sent          = $sent;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getReceiveCount(): int
    {
        return $this->receiveCount;
    }

    /**
     * @return int
     */
    public function getFirstReceived(): int
    {
        return $this->firstReceived;
    }

    /**
     * @return float
     */
    public function getSent(): float
    {
        return $this->sent;
    }
}