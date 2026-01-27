<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'stream_url',
        'access_type',
        'live_stream_package_id',
        'thumbnail',
        'category',
        'stream_type',
        'is_active',
        'is_featured',
        'start_time',
        'end_time',
        'view_count',
        'max_viewers',
        'sort_order',
        'quality_options',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'quality_options' => 'array',
        ];
    }

    /**
     * Scope to get only active streams.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured streams.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get currently available streams.
     */
    public function scopeAvailable($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_time')
                    ->orWhere('start_time', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', $now);
            });
    }

    /**
     * Increment view count.
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(LiveStreamPackage::class, 'live_stream_package_id');
    }
}

