<?php

namespace App\Http\Requests\LiveStreamPackage;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLiveStreamPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [$isUpdate ? 'nullable' : 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'duration_days' => [$isUpdate ? 'nullable' : 'required', 'integer', 'min:1', 'max:3650'],
            'price' => [$isUpdate ? 'nullable' : 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3', Rule::in(['USD', 'SYP', 'TRY'])],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}

