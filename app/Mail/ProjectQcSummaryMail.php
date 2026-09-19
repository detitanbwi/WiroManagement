<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectQcSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The project instance.
     */
    public Project $project;

    /**
     * Aggregated metrics payload.
     */
    public array $metrics;

    /**
     * Create a new message instance.
     */
    public function __construct(Project $project, array $metrics)
    {
        $this->project = $project;
        $this->metrics = $metrics;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $code = $this->metrics['project']['code'] ?? ('PRJ-' . $this->project->id);
        $title = $this->project->title;
        $date = now()->format('d M Y');

        return new Envelope(
            subject: "[QA/QC Summary] {$code} - {$title} ({$date})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.projects.qc-summary',
            with: [
                'project' => $this->project,
                'metrics' => $this->metrics,
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
}
