<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization check delegated to controller via $this->authorize('manage_system')
        // FormRequest::authorize() only returns true; the policy check is in the controller method.
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
                function ($attribute, $value, $fail) {
                    // If smtp_host is set, smtp_port should be required
                    if (! empty($this->input('smtp_host')) && empty($value)) {
                        $fail('The SMTP port is required when SMTP host is configured.');
                    }
                },
            ],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_from_address' => [
                'nullable',
                'email',
                function ($attribute, $value, $fail) {
                    // If smtp_host is set, smtp_from_address should be required
                    if (! empty($this->input('smtp_host')) && empty($value)) {
                        $fail('The SMTP from address is required when SMTP host is configured.');
                    }
                },
            ],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => [
                'nullable',
                'in:tls,ssl,none',
                function ($attribute, $value, $fail) {
                    // If smtp_host is set, smtp_encryption should be required
                    if (! empty($this->input('smtp_host')) && empty($value)) {
                        $fail('The SMTP encryption is required when SMTP host is configured.');
                    }
                },
            ],
            'session_lifetime' => ['required', 'integer', 'min:5'],
        ];
    }
}
