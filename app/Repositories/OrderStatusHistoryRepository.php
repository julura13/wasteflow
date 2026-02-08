<?php

namespace App\Repositories;

use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Collection;

class OrderStatusHistoryRepository extends BaseRepository
{
    public function __construct(OrderStatusHistory $model)
    {
        parent::__construct($model);
    }

    public function createForOrder(int $orderId, string $status, int $changedBy, ?string $notes = null): OrderStatusHistory
    {
        return $this->create([
            'order_id' => $orderId,
            'status' => $status,
            'changed_by' => $changedBy,
            'notes' => $notes ?? 'Status changed to ' . $status,
        ]);
    }

    public function getByOrderId(int $orderId): Collection
    {
        return $this->model->where('order_id', $orderId)
            ->with('changedBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLatestByOrderId(int $orderId): ?OrderStatusHistory
    {
        return $this->model->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}

