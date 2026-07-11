<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'attendance_machine_id' => ['nullable', 'integer', Rule::exists('attendance_machines', 'id')->whereNull('deleted_at')],
            'punch_at' => ['required', 'date'],
            'punch_type' => ['required', Rule::in(['in', 'out', 'unknown'])],
            'verification_type' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
