<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'label',
        'label_en',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get setting by key.
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Get setting value by key.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::getByKey($key);
        return $setting?->value ?? $default;
    }

    /**
     * Get all social links.
     */
    public static function getSocialLinks(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('type', 'social_link')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all general settings.
     */
    public static function getGeneralSettings(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('type', 'general_setting')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Update or create setting by key.
     */
    public static function updateOrCreateByKey(string $key, array $attributes): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            $attributes
        );
    }
}
