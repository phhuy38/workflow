<?php

namespace App\Http\Requests\Process;

use App\Models\ProcessInstance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LaunchProcessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ADR-019: LUÔN delegate sang Policy
        return $this->user()->can('create', ProcessInstance::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['required', 'exists:process_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'context_data' => ['nullable', 'array'],
        ];
    }
}
