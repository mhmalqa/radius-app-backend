<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashWithdrawalResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'description' => $this->description,
            'reference_number' => $this->reference_number,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'withdrawal_date' => $this->withdrawal_date->format('Y-m-d'),
            'attachments' => $this->attachments ?? [],
            'withdrawn_by' => [
                'id' => $this->withdrawnBy->id,
                'username' => $this->withdrawnBy->username,
                'firstname' => $this->withdrawnBy->firstname,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

