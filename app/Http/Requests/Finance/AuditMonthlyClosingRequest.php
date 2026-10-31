<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class AuditMonthlyClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audit_status' => 'required|in:draft,audited,approved',
            'auditor_notes' => 'nullable|string|max:3000',
        ];
    }
}
