<?php

namespace App\Http\Requests\Slide;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSlideRequest extends FormRequest
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
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        
        return [
            'title' => [$isUpdate ? 'nullable' : 'required', 'string', 'max:150'],
            'image' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB max
            'image_mobile' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'image_desktop' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'target_audience' => ['nullable', 'string', Rule::in(['all', 'active_users', 'expired_users'])],
            'sort_order' => ['nullable', 'integer'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
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
            'image.required' => 'الصورة مطلوبة',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'نوع الصورة يجب أن يكون: jpeg, jpg, png, webp',
            'image.max' => 'حجم الصورة يجب أن يكون أقل من 5 ميجابايت',
            'image_mobile.image' => 'صورة الموبايل يجب أن تكون صورة',
            'image_desktop.image' => 'صورة الديسكتوب يجب أن تكون صورة',
        ];
    }
}

