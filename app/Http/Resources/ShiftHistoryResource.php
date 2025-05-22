<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'description' => $this->description,
            'type' => $this->type,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}