<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LatencyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'round_up_to_hour' => ['nullable', 'boolean'],
            'multiplier' => ['required', 'numeric', 'min:0', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'round_up_to_hour' => $this->boolean('round_up_to_hour'),
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }
}
