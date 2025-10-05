<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'formatted_unit_price' => 'Rp ' . number_format($this->unit_price, 0, ',', '.'),
            'total_price' => $this->total_price,
            'formatted_total_price' => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
        ];
    }
}
