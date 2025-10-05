<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            
            // Amounts
            'subtotal' => $this->subtotal,
            'formatted_subtotal' => 'Rp ' . number_format($this->subtotal, 0, ',', '.'),
            'tax_amount' => $this->tax_amount,
            'formatted_tax_amount' => 'Rp ' . number_format($this->tax_amount, 0, ',', '.'),
            'shipping_amount' => $this->shipping_amount,
            'formatted_shipping_amount' => 'Rp ' . number_format($this->shipping_amount, 0, ',', '.'),
            'total_amount' => $this->total_amount,
            'formatted_total_amount' => 'Rp ' . number_format($this->total_amount, 0, ',', '.'),
            
            // Addresses
            'shipping_address' => $this->shipping_address,
            
            // Order items
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'items_count' => $this->whenLoaded('orderItems', function () {
                return $this->orderItems->count();
            }),
            
            // User info (only basic info for privacy)
            'customer' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'shipped_at' => $this->shipped_at?->format('Y-m-d H:i:s'),
            'delivered_at' => $this->delivered_at?->format('Y-m-d H:i:s'),
            
            // Additional info
            'notes' => $this->notes,
            'can_be_cancelled' => $this->canBeCancelled(),
        ];
    }
}
