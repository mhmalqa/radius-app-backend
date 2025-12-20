<?php

namespace App\Http\Requests\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePaymentMethodRequest extends FormRequest
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
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH') || $this->isMethod('POST');
        
        return [
            'name' => [$isUpdate ? 'nullable' : 'required', 'string', 'max:50'],
            'name_ar' => [$isUpdate ? 'nullable' : 'required', 'string', 'max:50'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'qr_code' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'code' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}

