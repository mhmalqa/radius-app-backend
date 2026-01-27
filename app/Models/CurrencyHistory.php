<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyHistory extends Model
{
    use HasFactory;

    public $timestamps = false; // We use created_at only manually or by database default

    protected $fillable = [
        'currency_id',
        'old_rate',
        'new_rate',
        'updated_by',
        'created_at',
    ];

    protected $casts = [
        'old_rate' => 'decimal:2',
        'new_rate' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Get the currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who made the update.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'updated_by');
    }
}
