<?php declare(strict_types=1);


namespace AndrewBreksa\RSMQ;

/**
 * Class QueueAttributes
 *
 * @package AndrewBreksa\RSMQ
 * @author  Andrew Breksa <andrew@andrewbreksa.com>
 */
class QueueAttributes
{

    /**
     * @var int
     */
    protected $vt;

    /**
     * @var int
     */
    protected $delay;

    /**
     * @var int
     */
    protected $maxSize;

    /**
     * @var int
     */
    protected $totalReceived;

    /**
     * @var int
     */
    protected $totalSent;

    /**
     * @var int
     */
    protected $created;

    /**
     * @var int
     */
    protected $modified;

    /**
     * @var int
     */
    protected $messageCount;

    /**
     * @var int
     */
    protected $hiddenMessageCount;

    /**
     * QueueAttributes constructor.
     *
     * @param int $vt
     * @param int $delay
     * @param int $maxSize
     * @param int $totalReceived
     * @param int $totalSent
     * @param int $created
     * @param int $modified
     * @param int $messageCount
     * @param int $hiddenMessageCount
     */
    public function __construct(
        int $vt,
        int $delay,
        int $maxSize,
        int $totalReceived,
        int $totalSent,
        int $created,
        int $modified,
        int $messageCount,
        int $hiddenMessageCount
    ) {
        $this->vt                 = $vt;
        $this->delay              = $delay;
        $this->maxSize            = $maxSize;
        $this->totalReceived      = $totalReceived;
        $this->totalSent          = $totalSent;
        $this->created            = $created;
        $this->modified           = $modified;
        $this->messageCount       = $messageCount;
        $this->hiddenMessageCount = $hiddenMessageCount;
    }

    /**
     * @return int
     */
    public function getVt(): int
    {
        return $this->vt;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @return int
     */
    public function getTotalReceived(): int
    {
        return $this->totalReceived;
    }

    /**
     * @return int
     */
    public function getTotalSent(): int
    {
        return $this->totalSent;
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @return int
     */
    public function getModified(): int
    {
        return $this->modified;
    }

    /**
     * @return int
     */
    public function getMessageCount(): int
    {
        return $this->messageCount;
    }

    /**
     * @return int
     */
    public function getHiddenMessageCount(): int
    {
        return $this->hiddenMessageCount;
    }

}