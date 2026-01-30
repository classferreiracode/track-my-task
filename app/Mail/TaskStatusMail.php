<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $type,
        public array $payload,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForType(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.task-status',
            with: [
                'type' => $this->type,
                'payload' => $this->payload,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    protected function subjectForType(): string
    {
        $title = $this->payload['task_title'] ?? 'Task';

        return match ($this->type) {
            'assigned' => "Task atribuída: {$title}",
            'status_changed' => "Task movida: {$title}",
            'overdue' => "Task atrasada: {$title}",
            'deleted' => "Task removida: {$title}",
            'completed' => "Task concluída: {$title}",
            default => "Atualização da task: {$title}",
        };
    }
}
