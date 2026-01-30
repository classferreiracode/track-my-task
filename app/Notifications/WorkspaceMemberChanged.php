<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceMemberChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Workspace $workspace,
        public string $type,
        public ?User $actor = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->type === 'left'
            ? 'Você saiu do workspace'
            : 'Você foi removido do workspace';

        $actorName = $this->actor?->name;
        $line = $this->type === 'left'
            ? "Você saiu do workspace {$this->workspace->name}."
            : "Você foi removido do workspace {$this->workspace->name}.";

        if ($actorName) {
            $line .= " Ação realizada por {$actorName}.";
        }

        return (new MailMessage)
            ->subject($subject)
            ->line($line)
            ->action('Acessar sistema', route('home'));
    }
}
