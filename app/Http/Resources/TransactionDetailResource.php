<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->trans_detail_id,
            'product' => new ProductResource($this->product),
            'quantity' => $this->qty,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'created_date' => $this->created_date,
            'created_by' => $this->creator?->name,
        ];
    }
} 