<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchedulingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_code_expiry_hours'                     => ['required', 'integer', 'min:1', 'max:720'],
            'departments'                              => ['required', 'array', 'min:1'],
            'departments.*.department_code'            => ['required', 'string', 'exists:department_scheduling_rules,department_code'],
            'departments.*.daily_capacity_limit'       => ['required', 'integer', 'min:1', 'max:500'],
            'departments.*.am_slot_limit'              => ['nullable', 'integer', 'min:0', 'max:500'],
            'departments.*.pm_slot_limit'              => ['nullable', 'integer', 'min:0', 'max:500'],
            'departments.*.booking_cutoff_time'        => ['required', 'date_format:H:i'],
            'departments.*.qr_expiry_hours_override'   => ['nullable', 'integer', 'min:1', 'max:720'],
            'departments.*.allow_same_day_booking'     => ['required', 'boolean'],
            'departments.*.is_active'                  => ['sometimes', 'boolean'],
        ];
    }
}
