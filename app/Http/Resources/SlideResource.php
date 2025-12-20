<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlideResource extends JsonResource
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
            'image_path' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'image_mobile' => $this->image_mobile ? asset('storage/' . $this->image_mobile) : null,
            'image_desktop' => $this->image_desktop ? asset('storage/' . $this->image_desktop) : null,
            'link_url' => $this->link_url,
            'is_active' => $this->is_active,
            'target_audience' => $this->target_audience,
            'sort_order' => $this->sort_order,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'click_count' => $this->click_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

