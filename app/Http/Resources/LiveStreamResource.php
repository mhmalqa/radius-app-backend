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
        $user = $request->user();
        $isAdmin = $user && $user->isAdmin();

        // For non-admin users, never expose the upstream stream_url.
        // We optionally attach a runtime property "secure_stream_url" from controllers.
        $streamUrl = $isAdmin ? $this->stream_url : ($this->secure_stream_url ?? null);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'stream_url' => $streamUrl,
            'access_type' => $this->access_type ?? 'all_subscribers',
            'access_type_label' => $this->access_type === 'live_subscribers_only' ? 'لمشتركي البث المباشر فقط' : 'لجميع المشتركين',
            'live_stream_package_id' => $this->live_stream_package_id,
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
            // For non-admin users, avoid exposing upstream URLs inside quality options.
            'quality_options' => $isAdmin ? $this->quality_options : $this->sanitizeQualityOptions($this->quality_options),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    protected function sanitizeQualityOptions($qualityOptions): ?array
    {
        if (!is_array($qualityOptions)) {
            return null;
        }

        return array_map(function ($opt) {
            if (!is_array($opt)) {
                return $opt;
            }

            // Keep label only; drop URL if present
            return [
                'label' => $opt['label'] ?? null,
            ];
        }, $qualityOptions);
    }
}

