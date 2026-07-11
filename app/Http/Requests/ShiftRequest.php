<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shift = $this->route('shift');

        return [
            'schedule_mode' => [$shift ? 'nullable' : 'required', Rule::in(['single', 'double'])],
            'schedule_name_ar' => [app()->isLocale('ar') ? 'required' : 'nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
            'second_start_time' => ['required_if:schedule_mode,double', 'nullable', 'date_format:H:i'],
            'second_end_time' => ['required_if:schedule_mode,double', 'nullable', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('start_time') >= $this->input('end_time')) {
                $validator->errors()->add('end_time', __('app.shift_time_order'));
            }

            if ($this->input('schedule_mode') !== 'double') {
                return;
            }

            if ($this->input('second_start_time') >= $this->input('second_end_time')) {
                $validator->errors()->add('second_end_time', __('app.shift_time_order'));
            }

            if ($this->input('second_start_time') < $this->input('end_time')) {
                $validator->errors()->add('second_start_time', __('app.shift_overlap'));
            }
        }];
    }
}
