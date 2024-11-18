<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->trans_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'date' => $this->date,
            'total_price' => $this->total_price,
            'payment_status' => $this->payment_status,
            'details' => TransactionDetailResource::collection($this->details),
            'created_date' => $this->created_date,
            'created_by' => $this->creator?->name,
            'updated_date' => $this->updated_date,
            'updated_by' => $this->updater?->name,
        ];
    }
} 