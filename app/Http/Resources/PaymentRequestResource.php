<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentRequestResource extends JsonResource
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
            'user' => new UserResource($this->whenLoaded('user')),
            'payment_type' => $this->payment_type ?? 'online',
            'payment_type_label' => $this->payment_type === 'cash' ? 'نقدي' : 'عبر الإنترنت',
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'period_months' => $this->period_months,
            'plan_name' => $this->plan_name,
            'payment_method' => $this->payment_method,
            'payment_method_details' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'transaction_number' => $this->transaction_number,
            'receipt_file' => $this->receipt_file ? asset('storage/' . $this->receipt_file) : null,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->status === 0 ? 'قيد المراجعة' : ($this->status === 1 ? 'مقبول' : 'مرفوض'),
            'is_paid' => $this->is_paid ?? false,
            'is_deferred' => $this->is_deferred ?? false,
            'payment_status_label' => $this->is_deferred ? 'دفع مؤجل' : ($this->is_paid ? 'مدفوع' : 'غير مدفوع'),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reject_reason' => $this->reject_reason,
            'notes' => $this->notes,
            'approved_amount' => $this->approved_amount ? (float) $this->approved_amount : null,
            'auto_approved' => $this->auto_approved,
            'paid_amount' => (float) ($this->paid_amount ?? 0),
            'remaining_amount' => (float) $this->remaining_amount,
            'is_fully_paid' => $this->isFullyPaid(),
            'partial_payments' => $this->when($this->relationLoaded('partialPayments'), function () {
                return $this->partialPayments->map(function ($partial) {
                    return [
                        'id' => $partial->id,
                        'amount' => (float) $partial->amount,
                        'currency' => $partial->currency,
                        'payment_date' => $partial->payment_date->format('Y-m-d'),
                        'notes' => $partial->notes,
                        'created_by' => $partial->creator 
                            ? new UserResource($partial->creator) 
                            : null,
                        'created_at' => $partial->created_at->toIso8601String(),
                    ];
                });
            }),
            'revenue' => $this->when($this->relationLoaded('revenue') && $this->revenue, function () {
                return [
                    'id' => $this->revenue->id,
                    'amount' => (float) $this->revenue->amount,
                    'currency' => $this->revenue->currency,
                    'period_months' => $this->revenue->period_months,
                    'payment_type' => $this->revenue->payment_type,
                    'payment_date' => $this->revenue->payment_date?->format('Y-m-d'),
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

