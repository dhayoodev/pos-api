<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'stock_id' => $this->stock_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'note' => $this->note,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'created_by' => $this->created_by,
            'user' => new UserResource($this->whenLoaded('user')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'stock' => new StockProductResource($this->whenLoaded('stock')),
            'creator' => new UserResource($this->whenLoaded('createdBy'))
        ];
    }
}