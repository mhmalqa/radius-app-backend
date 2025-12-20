<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'icon' => $this->icon ? asset('storage/' . $this->icon) : null,
            'qr_code' => $this->qr_code ? asset('storage/' . $this->qr_code) : $this->qr_code,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'instructions' => $this->instructions,
            'sort_order' => $this->sort_order,
        ];
    }
}

