<?php

namespace SoftHouse\MonitoringService\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SoftHouse\MonitoringService\IncomingEntry;

class MonitoringJobEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public IncomingEntry $entry;

    public function __construct(IncomingEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('monitoring-job');
    }
}
