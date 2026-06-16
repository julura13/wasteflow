<?php

namespace App\Notifications;

use App\Mail\RecurringOrdersSummaryMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Notification;

class RecurringOrdersSummaryNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<array<string, mixed>>  $created
     * @param  list<array<string, mixed>>  $skipped
     */
    public function __construct(
        public readonly string $date,
        public readonly array $created,
        public readonly array $skipped,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (config('communicator.enabled', false)) {
            return ['communicator'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return new RecurringOrdersSummaryMail($this->date, $this->created, $this->skipped);
    }

    /**
     * @return array{subject: string, text: string}
     */
    public function toCommunicator(object $notifiable): array
    {
        return [
            'subject' => $this->subjectString(),
            'text' => $this->plainTextBody(),
        ];
    }

    private function subjectString(): string
    {
        $count = count($this->created);
        $label = $count === 1 ? '1 order created' : "{$count} orders created";

        return "Recurring Orders Summary – {$this->date} ({$label})";
    }

    private function plainTextBody(): string
    {
        $lines = [
            'Recurring orders summary for '.$this->date,
            '',
            'Created ('.count($this->created).'):',
        ];

        if ($this->created === []) {
            $lines[] = 'No orders were created — no active templates matched today.';
        } else {
            foreach ($this->created as $row) {
                $lines[] = '- '.$this->rowLabel($row).' — tracking '.($row['tracking_number'] ?? '—');
            }
        }

        $lines[] = '';
        $lines[] = 'Skipped, already existed ('.count($this->skipped).'):';

        if ($this->skipped === []) {
            $lines[] = '(none)';
        } else {
            foreach ($this->skipped as $row) {
                $lines[] = '- '.$this->rowLabel($row);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowLabel(array $row): string
    {
        $parts = array_filter([
            $row['company'] ?? null,
            $row['branch'] ?? null,
            $row['site'] ?? null,
        ]);

        return implode(' / ', $parts).' ('.($row['containers'] ?? '').')';
    }
}
