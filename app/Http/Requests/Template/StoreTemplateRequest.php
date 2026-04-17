<?php

namespace App\Http\Requests\Template;

use App\Models\ProcessTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProcessTemplate::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('process_templates', 'name')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên template đã tồn tại.',
        ];
    }
}
