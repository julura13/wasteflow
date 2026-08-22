<?php

namespace App\Traits;

use App\Models\ContainerOption;

trait HandlesOrderEditReasonsTrait
{
    protected function getDeletionReasonLabel(string $reason, string $details): string
    {
        $labels = [
            'incorrect_order' => 'Incorrect order',
            'duplicate' => 'Duplicate order',
            'wrong_date' => 'Wrong collection date',
            'wrong_site' => 'Wrong site / collection point',
            'cancelled_by_client' => 'Cancelled by client',
            'other' => 'Other'.($details ? ": {$details}" : ''),
        ];

        return $labels[$reason] ?? $reason;
    }

    /**
     * Validation rules for "reason for edit" (order update, weights, collection date).
     *
     * @return array<string, mixed>
     */
    protected function getEditReasonValidationRules(): array
    {
        return [
            'reason' => 'required|string|in:client_request,wrong_quantity,wrong_container_type,date_correction,data_entry_error,other',
            'reason_details' => 'required_if:reason,other|nullable|string|max:1000',
        ];
    }

    protected function getEditReasonLabel(string $reason, string $details): string
    {
        $labels = [
            'client_request' => 'Client request',
            'wrong_quantity' => 'Wrong quantity entered',
            'wrong_container_type' => 'Wrong container type',
            'date_correction' => 'Date correction',
            'data_entry_error' => 'Data entry error',
            'other' => 'Other'.($details ? ": {$details}" : ''),
        ];

        return $labels[$reason] ?? $reason;
    }

    protected function mapQuantityLinesWithContainerNames(array $lines): array
    {
        $containerOptionIds = collect($lines)->pluck('container_option_id')->unique()->filter()->all();
        $containerOptions = ContainerOption::whereIn('id', $containerOptionIds)->get()->keyBy('id');

        return collect($lines)->map(function ($line) use ($containerOptions) {
            $option = $containerOptions->get((int) $line['container_option_id']);
            $row = [
                'container_option_id' => (int) $line['container_option_id'],
                'container_option_name' => $option ? $option->name : '',
                'quantity' => (int) $line['quantity'],
            ];
            if (! empty($line['description'])) {
                $row['description'] = $line['description'];
            }

            return $row;
        })->all();
    }
}
