<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
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
            'user' => new UserResource($this->whenLoaded('user')),
            'radius_username' => $this->radius_username,
            'expiration_at' => $this->expiration_at?->toIso8601String(),
            'balance' => $this->balance ? (float) $this->balance : null,
            'data_limit' => $this->data_limit,
            'data_used' => $this->data_used,
            'data_usage_percentage' => $this->getDataUsagePercentage(),
            'remaining_data' => $this->getRemainingData(),
            'plan_name' => $this->plan_name,
            'auto_renew' => $this->auto_renew,
            'is_expired' => $this->isExpired(),
            'is_active' => $this->isActive(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'sync_status' => $this->sync_status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

