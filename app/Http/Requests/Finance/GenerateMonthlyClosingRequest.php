<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMonthlyClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'opening_cash_balance' => 'nullable|numeric|min:0',
            'auditor_notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'period_month.regex' => 'Format bulan harus berupa YYYY-MM (contoh: 2026-10).',
        ];
    }
}
