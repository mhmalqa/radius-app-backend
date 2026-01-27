<?php

namespace App\Http\Requests\PaymentRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequestStatus extends FormRequest
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
        $paymentRequest = $this->route('paymentRequest');
        $purpose = $paymentRequest?->purpose ?? 'internet_subscription';
        $requiresPeriodMonths = $purpose !== 'live_stream_subscription';

        $periodMonthsRules = ['nullable', 'integer', 'min:1', 'max:12'];
        if ($requiresPeriodMonths) {
            $periodMonthsRules[] = 'required_if:status,1';
        }

        return [
            'status' => ['required', 'integer', Rule::in([1, 2])], // 1: approved, 2: rejected
            'reject_reason' => ['required_if:status,2', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'approved_amount' => ['nullable', 'numeric', 'min:0.01'],
            'period_months' => $periodMonthsRules,
            'plan_name' => ['nullable', 'string', 'max:100'],
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
            'status.required' => 'الحالة مطلوبة',
            'status.in' => 'الحالة غير صحيحة',
            'reject_reason.required_if' => 'سبب الرفض مطلوب عند رفض الطلب',
            'period_months.required_if' => 'عدد أشهر التجديد مطلوب عند قبول الطلب',
            'period_months.integer' => 'عدد الأشهر يجب أن يكون رقماً صحيحاً',
            'period_months.min' => 'عدد الأشهر يجب أن يكون على الأقل شهر واحد',
            'period_months.max' => 'عدد الأشهر يجب أن يكون على الأكثر 12 شهر',
            'plan_name.max' => 'اسم الخطة يجب أن يكون أقل من 100 حرف',
        ];
    }
}

