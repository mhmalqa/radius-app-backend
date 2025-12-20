<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')->id ?? null;

        return [
            'username' => ['sometimes', 'string', 'max:100', Rule::unique('app_users', 'username')->ignore($userId)],
            'password' => ['sometimes', 'string', 'min:6'],
            'firstname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'language' => ['nullable', 'string', Rule::in(['ar', 'en'])],
            'is_active' => ['sometimes', 'boolean'],
            'live_access' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'integer', Rule::in([0, 1, 2])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'اسم المستخدم مستخدم بالفعل',
            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 6 أحرف',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'role.in' => 'الدور غير صحيح',
        ];
    }
}

