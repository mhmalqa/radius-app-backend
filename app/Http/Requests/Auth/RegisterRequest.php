<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:100', 'unique:app_users,username'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'language' => ['nullable', 'string', Rule::in(['ar', 'en'])],
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
            'username.required' => 'اسم المستخدم مطلوب',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل. يرجى استخدام اسم مستخدم آخر أو تسجيل الدخول إذا كان لديك حساب.',
            'username.max' => 'اسم المستخدم يجب أن يكون أقل من 100 حرف',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 6 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق. يرجى التأكد من تطابق كلمة المرور وتأكيدها.',
            'phone.max' => 'رقم الهاتف يجب أن يكون أقل من 20 حرف',
            'email.email' => 'البريد الإلكتروني غير صحيح. يرجى إدخال بريد إلكتروني صالح.',
            'email.max' => 'البريد الإلكتروني يجب أن يكون أقل من 255 حرف',
            'language.in' => 'اللغة المحددة غير مدعومة. الرجاء اختيار اللغة العربية (ar) أو الإنجليزية (en).',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // Get the first error message (the most important one)
        $errors = $validator->errors();
        $firstError = $errors->first();

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $firstError,
            ], 422)
        );
    }
}

