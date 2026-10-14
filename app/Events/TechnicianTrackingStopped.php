<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianTrackingStopped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $technicianId;
    public int $timestamp;

    public function __construct(string $technicianId)
    {
        $this->technicianId = $technicianId;
        $this->timestamp = (int) (microtime(true) * 1000);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin-live-map'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin:technician_tracking_stopped';
    }

    public function broadcastWith(): array
    {
        return [
            'technicianId' => $this->technicianId,
            'timestamp' => $this->timestamp,
        ];
    }
}
