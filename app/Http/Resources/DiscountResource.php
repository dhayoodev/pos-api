<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'updated_at' => $this->updated_at,
            'updated_by' => new UserResource($this->whenLoaded('updatedBy')),
        ];
    }
}