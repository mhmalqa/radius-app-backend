<?php

namespace App\Http\Requests\LiveStream;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLiveStreamRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'stream_url' => [$isUpdate ? 'nullable' : 'required', 'url', 'max:500'],
            'access_type' => ['nullable', 'string', Rule::in(['all_subscribers', 'live_subscribers_only'])],
            // استبدال رابط الصورة بملف صورة حقيقي يُرفع من لوحة التحكم
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'category' => ['nullable', 'string', 'max:50', Rule::in(['match', 'channel', 'event'])],
            'stream_type' => ['nullable', 'string', Rule::in(['live', 'vod'])],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after:start_time'],
            'max_viewers' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer'],
            'quality_options' => ['nullable', 'array'],
        ];
    }
}

