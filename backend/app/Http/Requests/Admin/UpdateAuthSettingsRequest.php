<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp_provider'        => ['required', 'in:smtp,semaphore,twilio,log'],
            'otp_api_url'         => ['nullable', 'url', 'max:255'],
            'otp_api_key'         => ['nullable', 'string', 'max:255'],
            'otp_sender_id'       => ['required', 'string', 'max:60'],
            'otp_expiry_minutes'  => ['required', 'integer', 'min:2', 'max:30'],
            'otp_max_attempts'    => ['required', 'integer', 'min:3', 'max:10'],
            'username_min_length' => ['required', 'integer', 'min:4', 'max:16'],
            'username_max_length' => ['required', 'integer', 'gte:username_min_length', 'max:50'],
            'username_pattern'    => ['required', 'in:alphanumeric,alphanumeric_dash,patient_id_prefix'],
            'username_prefix'     => ['nullable', 'alpha_num', 'max:10'],
        ];
    }
}
