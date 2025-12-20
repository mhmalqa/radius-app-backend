<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawn_by',
        'amount',
        'currency',
        'reason',
        'description',
        'reference_number',
        'category',
        'withdrawal_date',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'withdrawal_date' => 'date',
            'attachments' => 'array',
        ];
    }

    /**
     * Get the user who made the withdrawal.
     */
    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'withdrawn_by');
    }

    /**
     * Get category label in Arabic.
     */
    public function getCategoryLabelAttribute(): string
    {
        $labels = [
            'operational' => 'تشغيلية',
            'maintenance' => 'صيانة',
            'salary' => 'رواتب',
            'utilities' => 'فواتير',
            'supplies' => 'مستلزمات',
            'marketing' => 'تسويق',
            'emergency' => 'طوارئ',
            'other' => 'أخرى',
        ];

        return $labels[$this->category] ?? $this->category;
    }
}

