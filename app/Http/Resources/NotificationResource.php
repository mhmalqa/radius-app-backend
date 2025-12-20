<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class NotificationResource extends JsonResource
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
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'priority' => $this->priority,
            'action_url' => $this->action_url,
            'action_text' => $this->action_text,
            'icon' => $this->icon,
            'sound' => $this->sound,
            'badge' => $this->badge,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'is_read' => $this->when($this->pivot, function () {
                return (bool) ($this->pivot->is_read ?? false);
            }, false),
            'read_at' => $this->when($this->pivot && $this->pivot->read_at, function () {
                $readAt = $this->pivot->read_at;
                if ($readAt instanceof Carbon || $readAt instanceof \DateTime) {
                    return $readAt->toIso8601String();
                }
                return $readAt ? Carbon::parse($readAt)->toIso8601String() : null;
            }),
            'is_sent' => $this->when($this->pivot, function () {
                return (bool) ($this->pivot->is_sent ?? false);
            }, false),
            'sent_at' => $this->when($this->pivot && $this->pivot->sent_at, function () {
                $sentAt = $this->pivot->sent_at;
                if ($sentAt instanceof Carbon || $sentAt instanceof \DateTime) {
                    return $sentAt->toIso8601String();
                }
                return $sentAt ? Carbon::parse($sentAt)->toIso8601String() : null;
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

