<?php

namespace App\Events;

use App\Models\Task;
use App\Models\TaskColumn;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?int $boardId;

    public ?int $actorId;

    public ?string $actorName;

    /**
     * Create a new event instance.
     */
    public function __construct(public Task $task)
    {
        $this->task->load('taskColumn.board');
        $this->boardId = $this->task->taskColumn?->task_board_id
            ?? TaskColumn::query()
                ->whereKey($this->task->task_column_id)
                ->value('task_board_id');
        $this->actorId = $this->resolveActorId();
        $this->actorName = $this->resolveActorName();
        $this->dontBroadcastToCurrentUser();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if (! $this->boardId) {
            return [];
        }

        return [
            new PresenceChannel('boards.'.$this->boardId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.status.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task' => [
                'id' => $this->task->id,
                'column_id' => $this->task->task_column_id,
                'sort_order' => $this->task->sort_order,
                'is_completed' => $this->task->is_completed,
                'completed_at' => $this->task->completed_at?->toIso8601String(),
                'board_id' => $this->boardId,
            ],
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
        ];
    }

    private function resolveActorId(): ?int
    {
        return request()->user()?->id;
    }

    private function resolveActorName(): ?string
    {
        return request()->user()?->name;
    }
}
