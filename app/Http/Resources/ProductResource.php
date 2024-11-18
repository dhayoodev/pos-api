<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->product_id,
            'category' => new ProductCategoryResource($this->category),
            'name' => $this->product_name,
            'picture' => $this->picture,
            'stock' => $this->stock,
            'price' => $this->price,
            'description' => $this->desc_product,
            'discount' => [
                'type' => $this->discount_type,
                'amount' => $this->discount_amount,
                'start_date' => $this->start_date_disc,
                'end_date' => $this->end_date_disc,
            ],
            'created_date' => $this->created_date,
            'created_by' => $this->creator?->name,
            'updated_date' => $this->updated_date,
            'updated_by' => $this->updater?->name,
        ];
    }
} 