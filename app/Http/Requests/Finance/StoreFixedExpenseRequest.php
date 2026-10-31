<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedExpenseRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:OPEX,UTILITY,SUBSCRIPTION,PAYROLL,INFRASTRUCTURE,opex,utility,subscription,payroll,infrastructure',
            'amount' => 'required|numeric|gt:0',
            'due_day_of_month' => 'required|integer|between:1,31',
            'payment_method' => 'required|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengeluaran tetap wajib diisi.',
            'category.required' => 'Kategori pengeluaran tetap wajib dipilih.',
            'category.in' => 'Kategori harus salah satu dari: OPEX, UTILITY, SUBSCRIPTION, PAYROLL, INFRASTRUCTURE.',
            'amount.required' => 'Nominal pengeluaran wajib diisi.',
            'amount.numeric' => 'Nominal pengeluaran harus berupa angka.',
            'amount.gt' => 'Nominal pengeluaran harus lebih besar dari 0.',
            'due_day_of_month.required' => 'Tanggal jatuh tempo bulanan wajib diisi.',
            'due_day_of_month.integer' => 'Tanggal jatuh tempo harus berupa bilangan bulat.',
            'due_day_of_month.between' => 'Tanggal jatuh tempo harus di antara hari ke-1 sampai 31.',
            'payment_method.required' => 'Metode pembayaran wajib diisi.',
        ];
    }
}
