<?php

namespace SoftHouse\MonitoringService\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SoftHouse\MonitoringService\IncomingEntry;

class MonitoringQueueEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public bool $isStop = false;
    public $time = null;

    public function __construct(bool $isStop, $time = null)
    {
        $this->isStop = $isStop;
        $this->time = $time;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|PrivateChannel
     */
    public function broadcastOn(): Channel|PrivateChannel
    {
        return new PrivateChannel('monitoring-schedule');
    }
}
