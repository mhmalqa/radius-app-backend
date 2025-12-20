<?php

namespace App\Http\Requests\CashWithdrawal;

use Illuminate\Foundation\Http\FormRequest;

class CreateCashWithdrawalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->isAdmin() || $this->user()->isAccountant());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency' => ['required', 'string', 'in:USD,SYP,TRY'],
            'reason' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'in:operational,maintenance,salary,utilities,supplies,marketing,emergency,other'],
            'withdrawal_date' => ['required', 'date', 'before_or_equal:today'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'amount.max' => 'المبلغ كبير جداً',
            'currency.required' => 'العملة مطلوبة',
            'currency.in' => 'العملة يجب أن تكون USD أو SYP أو TRY',
            'reason.required' => 'سبب السحب مطلوب',
            'reason.max' => 'سبب السحب طويل جداً',
            'category.required' => 'فئة السحب مطلوبة',
            'category.in' => 'فئة السحب غير صحيحة',
            'withdrawal_date.required' => 'تاريخ السحب مطلوب',
            'withdrawal_date.date' => 'تاريخ السحب غير صحيح',
            'withdrawal_date.before_or_equal' => 'تاريخ السحب لا يمكن أن يكون في المستقبل',
        ];
    }
}

