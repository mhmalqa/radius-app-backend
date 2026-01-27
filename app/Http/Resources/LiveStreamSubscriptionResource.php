<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveStreamSubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'package' => new LiveStreamPackageResource($this->whenLoaded('package')),
            'payment_request_id' => $this->payment_request_id,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'status' => $this->status,
            'is_active' => $this->expires_at?->isFuture() && $this->status === 'active',
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'renewed_from_id' => $this->renewed_from_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

