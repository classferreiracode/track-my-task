<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskMentioned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Task $task,
        public TaskComment $comment,
        public User $actor,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $taskTitle = $this->task->title;
        $actorName = $this->actor->name;

        return (new MailMessage)
            ->subject("Você foi mencionado em: {$taskTitle}")
            ->line("{$actorName} mencionou você em um comentário.")
            ->line($this->comment->body)
            ->action('Abrir task', route('tasks.index', [
                'workspace' => $this->task->taskColumn?->board?->workspace_id,
            ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $board = $this->task->taskColumn?->board;
        $workspaceId = $board?->workspace_id;
        $boardId = $board?->id;

        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'comment_id' => $this->comment->id,
            'comment_body' => $this->comment->body,
            'workspace_id' => $workspaceId,
            'board_id' => $boardId,
            'url' => $workspaceId
                ? route('tasks.index', [
                    'workspace' => $workspaceId,
                    'board' => $boardId,
                    'task' => $this->task->id,
                ])
                : route('tasks.index', [
                    'task' => $this->task->id,
                ]),
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'email' => $this->actor->email,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
