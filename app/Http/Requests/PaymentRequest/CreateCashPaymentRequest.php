<?php

namespace App\Http\Requests\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCashPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $purpose = $this->input('purpose', 'internet_subscription');
        $isLive = $purpose === 'live_stream_subscription';

        $periodMonthsRules = ['nullable', 'integer', 'min:1', 'max:12'];
        if (!$isLive) {
            $periodMonthsRules[] = 'required';
        }

        return [
            'user_id' => ['required', 'integer', 'exists:app_users,id'],
            'purpose' => ['nullable', 'string', Rule::in(['internet_subscription', 'live_stream_subscription'])],
            'package_id' => ['required_if:purpose,live_stream_subscription', 'integer', 'exists:live_stream_packages,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:3', Rule::in(['USD', 'SYP', 'TRY'])],
            'period_months' => $periodMonthsRules,
            'plan_name' => ['nullable', 'string', 'max:100'],
            'is_deferred' => ['nullable', 'boolean'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'user_id.required' => 'المستخدم مطلوب',
            'user_id.exists' => 'المستخدم غير موجود',
            'package_id.required_if' => 'الباقة مطلوبة لاشتراك البث المباشر',
            'package_id.exists' => 'الباقة غير موجودة',
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'period_months.required' => 'عدد أشهر التجديد مطلوب',
            'period_months.integer' => 'عدد الأشهر يجب أن يكون رقماً صحيحاً',
            'period_months.min' => 'عدد الأشهر يجب أن يكون على الأقل شهر واحد',
            'period_months.max' => 'عدد الأشهر يجب أن يكون على الأكثر 12 شهر',
        ];
    }
}
