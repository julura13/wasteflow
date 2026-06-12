<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecurringOrdersSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $date  The date orders were created for (Y-m-d)
     * @param  list<array<string, mixed>>  $created  Orders that were created
     * @param  list<array<string, mixed>>  $skipped  Templates skipped (order already existed)
     * @param  list<array<string, mixed>>  $inactive  Templates skipped (no matching day or inactive)
     */
    public function __construct(
        public readonly string $date,
        public readonly array $created,
        public readonly array $skipped,
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->created);
        $label = $count === 1 ? '1 order created' : "{$count} orders created";

        return new Envelope(
            subject: "Recurring Orders Summary – {$this->date} ({$label})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recurring-orders-summary',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
