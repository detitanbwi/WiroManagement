<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'work_date' => 'required_without:date_ranges|nullable|date',
            'end_date' => 'nullable|date|after_or_equal:work_date',
            'hours_per_day' => 'nullable|numeric|min:0.1|max:24',
            'days_count' => 'nullable|integer|min:1',
            'hours_worked' => 'nullable|numeric|min:0.1',
            'date_ranges' => 'nullable|array',
            'date_ranges.*.start_date' => 'required|date',
            'date_ranges.*.end_date' => 'required|date|after_or_equal:date_ranges.*.start_date',
            'date_ranges.*.hours_per_day' => 'required|numeric|min:0.5|max:24',
            'date_ranges.*.is_weekend' => 'nullable|boolean',
            'date_ranges.*.is_overtime' => 'nullable|boolean',
            'role_in_project' => 'nullable|string|max:100',
            'skill_level' => 'nullable|string|in:entry,middle,senior',
            'source_account_type' => 'required|in:office_account,personal_reimbursement',
            'base_rate' => 'nullable|numeric|min:0',
            'is_weekend' => 'nullable|boolean',
            'is_overtime' => 'nullable|boolean',
            'payment_status' => 'required|in:unpaid,pending_approval,paid',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'audit_notes' => 'nullable|string|max:1000',
            'redirect_to' => 'nullable|string|max:500',
        ];
    }
}
