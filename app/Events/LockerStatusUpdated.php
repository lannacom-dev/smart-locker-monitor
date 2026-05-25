<?php

namespace App\Events;

use App\Models\Locker;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LockerStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $lockerId,
        public readonly string $lockerName,
        public readonly int    $companyId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly ?int   $changedBy,
        public readonly string $changedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->companyId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'locker.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'locker_id'    => $this->lockerId,
            'locker_name'  => $this->lockerName,
            'company_id'   => $this->companyId,
            'old_status'   => $this->oldStatus,
            'new_status'   => $this->newStatus,
            'changed_by'   => $this->changedBy,
            'changed_at'   => $this->changedAt,
        ];
    }
}
