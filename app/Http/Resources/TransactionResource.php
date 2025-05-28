<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'shift_id' => $this->shift_id,
            'discount_id' => $this->discount_id,
            'payment_method' => $this->payment_method,
            'total_subtotal' => $this->total_subtotal,
            'total_price' => $this->total_price,
            'total_payment' => $this->total_payment,
            'total_tax' => $this->total_tax,
            'type_discount' => $this->type_discount,
            'amount_discount' => $this->amount_discount,
            'payment_status' => $this->payment_status,
            'date' => $this->date,
            'is_deleted' => $this->is_deleted,
            'type_reason' => $this->type_reason,
            'reason' => $this->reason,
            'details' => TransactionDetailResource::collection($this->details),
            'created_at' => $this->created_at,
            'created_by' => $this->creator?->name,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updater?->name,
        ];
    }
}
