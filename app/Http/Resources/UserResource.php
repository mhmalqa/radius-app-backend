<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'firstname' => $this->firstname,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'live_access' => $this->live_access,
            // Live stream packages (only when loaded; mainly for admin user details)
            'live_stream_subscriptions' => $this->whenLoaded('liveStreamSubscriptions', function () {
                return $this->liveStreamSubscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'package' => $sub->relationLoaded('package') && $sub->package
                            ? [
                                'id' => $sub->package->id,
                                'name' => $sub->package->name,
                                'duration_days' => (int) $sub->package->duration_days,
                                'price' => (float) $sub->package->price,
                                'currency' => $sub->package->currency,
                            ]
                            : null,
                        'payment_request_id' => $sub->payment_request_id,
                        'starts_at' => $sub->starts_at?->toIso8601String(),
                        'expires_at' => $sub->expires_at?->toIso8601String(),
                        'status' => $sub->status,
                        'is_active' => $sub->expires_at?->isFuture() && $sub->status === 'active',
                    ];
                });
            }),
            // Computed flags to simplify frontend logic
            'has_active_live_package' => $this->when($this->relationLoaded('liveStreamSubscriptions'), function () {
                return $this->liveStreamSubscriptions
                    ->where('status', 'active')
                    ->filter(fn ($s) => $s->expires_at && $s->expires_at->isFuture())
                    ->isNotEmpty();
            }),
            'role' => $this->role,
            'language' => $this->language,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

