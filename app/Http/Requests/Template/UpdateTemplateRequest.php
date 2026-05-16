<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('process_template'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('process_templates', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('process_template')->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name ?? ''),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên template đã tồn tại.',
        ];
    }
}
