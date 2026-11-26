<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_timeout_minutes'         => ['required', 'integer', 'min:5', 'max:480'],
            'patient_session_timeout_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'auto_backup_enabled'             => ['required', 'boolean'],
            'auto_backup_frequency'           => ['required_if:auto_backup_enabled,true', 'in:daily,weekly'],
        ];
    }
}
