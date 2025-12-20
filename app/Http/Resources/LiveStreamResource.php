<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveStreamResource extends JsonResource
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
            'description' => $this->description,
            'stream_url' => $this->stream_url,
            'access_type' => $this->access_type ?? 'all_subscribers',
            'access_type_label' => $this->access_type === 'live_subscribers_only' ? 'لمشتركي البث المباشر فقط' : 'لجميع المشتركين',
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'category' => $this->category,
            'stream_type' => $this->stream_type,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'view_count' => $this->view_count,
            'max_viewers' => $this->max_viewers,
            'sort_order' => $this->sort_order,
            'quality_options' => $this->quality_options,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

