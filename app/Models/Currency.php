<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_default',
        'is_active',
        'last_updated_at',
        'updated_by',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Get the user who last updated the currency.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'updated_by');
    }

    /**
     * Get history of rate changes.
     */
    public function history(): HasMany
    {
        return $this->hasMany(CurrencyHistory::class)->orderBy('created_at', 'desc');
    }
}
