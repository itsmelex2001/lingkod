<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_name'     => ['required', 'string', 'max:180'],
            'hospital_tagline'  => ['nullable', 'string', 'max:255'],
            'contact_phone'     => ['required', 'string', 'max:40'],
            'emergency_hotline' => ['required', 'string', 'max:80'],
            'contact_email'     => ['required', 'email', 'max:120'],
            'hospital_address'  => ['required', 'string', 'max:300'],
            'clinic_hours'      => ['required', 'array'],
            'clinic_hours.*.is_open' => ['required', 'boolean'],
            'clinic_hours.*.open'    => ['required', 'date_format:H:i'],
            'clinic_hours.*.close'   => ['required', 'date_format:H:i'],
            'hospital_holidays'          => ['nullable', 'array', 'max:100'],
            'hospital_holidays.*.date'   => ['required', 'date_format:Y-m-d'],
            'hospital_holidays.*.label'  => ['required', 'string', 'max:120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $hours = $this->input('clinic_hours', []);
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                foreach ($days as $day) {
                    if (!isset($hours[$day])) {
                        $validator->errors()->add("clinic_hours.$day", "Missing schedule for $day.");
                        continue;
                    }
                    $entry = $hours[$day];
                    if (!empty($entry['is_open']) && ($entry['close'] ?? '') <= ($entry['open'] ?? '')) {
                        $validator->errors()->add("clinic_hours.$day.close", 'Closing time must be later than opening time.');
                    }
                }
            },
        ];
    }
}
