<?php

namespace App\Events;

use App\Models\TaskActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskActivityCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public TaskActivity $activity)
    {
        $this->activity->loadMissing('user');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('tasks.'.$this->activity->task_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.activity.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'activity' => [
                'id' => $this->activity->id,
                'type' => $this->activity->type,
                'meta' => $this->activity->meta,
                'created_at' => $this->activity->created_at?->toIso8601String(),
                'user' => $this->activity->user ? [
                    'id' => $this->activity->user->id,
                    'name' => $this->activity->user->name,
                    'email' => $this->activity->user->email,
                ] : null,
            ],
        ];
    }
}
