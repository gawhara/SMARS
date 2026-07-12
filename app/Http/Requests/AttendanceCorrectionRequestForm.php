<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceCorrectionRequestForm extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'attendance_record_id' => ['nullable', 'integer', Rule::exists('attendance_records', 'id')->whereNull('deleted_at')],
            'requested_punch_at' => ['required', 'date'],
            'requested_punch_type' => ['required', Rule::in(['in', 'out'])],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
