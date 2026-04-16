<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Policy in controller
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }
}
