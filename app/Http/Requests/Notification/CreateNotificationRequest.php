<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
            'type' => ['nullable', 'string', Rule::in(['system', 'manual'])],
            'priority' => ['nullable', 'integer', Rule::in([0, 1, 2])],
            'action_url' => ['nullable', 'url', 'max:255'],
            'action_text' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sound' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'integer'],
            'target_type' => ['nullable', 'string', Rule::in(['all', 'active', 'specific'])],
            'user_ids' => ['nullable', 'array', 'required_if:target_type,specific'],
            'user_ids.*' => ['integer', 'exists:app_users,id'],
        ];
    }
}

