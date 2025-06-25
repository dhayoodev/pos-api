<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'cash_balance' => $this->cash_balance,
            'expected_cash_balance' => $this->expected_cash_balance,
            'final_cash_balance' => $this->final_cash_balance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'histories' => ShiftHistoryResource::collection($this->whenLoaded('histories')),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'products' => $this->products,
        ];
    }
}