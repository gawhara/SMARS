<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Clear the address field that doesn't apply to the chosen connection type.
        if ($this->input('connection_type') === 'ddns') {
            $this->merge(['ip_address' => null]);
        } else {
            $this->merge(['domain' => null]);
        }
    }

    public function rules(): array
    {
        $machine = $this->route('device');
        $connection = $this->input('connection_type');
        $companyId = $this->integer('company_id');

        return [
            'device_name' => ['required', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:120', Rule::unique('attendance_machines', 'serial_number')->ignore($machine)],

            'connection_type' => ['required', Rule::in(['lan', 'vpn', 'ddns', 'static_ip'])],
            'ip_address' => [Rule::requiredIf(in_array($connection, ['lan', 'vpn', 'static_ip'], true)), 'nullable', 'ip'],
            'domain' => [Rule::requiredIf($connection === 'ddns'), 'nullable', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:255'],

            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'location_description' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ip_address' => __('app.device.ip_address'),
            'domain' => __('app.device.domain'),
            'port' => __('app.device.port'),
        ];
    }
}
