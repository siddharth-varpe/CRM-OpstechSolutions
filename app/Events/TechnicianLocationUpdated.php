<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $record;

    public function __construct(array $record)
    {
        $this->record = $record;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin-live-map'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin:technician_location_update';
    }

    public function broadcastWith(): array
    {
        return $this->record;
    }
}
