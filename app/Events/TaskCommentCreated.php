<?php

namespace App\Events;

use App\Models\TaskComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public TaskComment $comment)
    {
        $this->comment->loadMissing(['user', 'mentions']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('tasks.'.$this->comment->task_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.comment.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'comment' => [
                'id' => $this->comment->id,
                'body' => $this->comment->body,
                'created_at' => $this->comment->created_at?->toIso8601String(),
                'user' => [
                    'id' => $this->comment->user?->id,
                    'name' => $this->comment->user?->name,
                    'email' => $this->comment->user?->email,
                ],
                'mentions' => $this->comment->mentions->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
        ];
    }
}
