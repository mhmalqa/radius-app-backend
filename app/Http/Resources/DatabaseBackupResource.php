<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseBackupResource extends JsonResource
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
            'filename' => $this->filename,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size ? (int) $this->file_size : null,
            'file_size_human' => $this->file_size_human,
            'type' => $this->type,
            'type_label' => $this->type === 'manual' ? 'يدوي' : 'تلقائي',
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'error_message' => $this->error_message,
            'scheduled_time' => $this->scheduled_time,
            'backup_date' => $this->backup_date?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get status label in Arabic.
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            default => $this->status,
        };
    }
}
