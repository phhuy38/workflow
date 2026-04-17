<?php

namespace App\Http\Requests\Step;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('step_definition')->processTemplate);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_type' => ['nullable', 'in:user,role,department'],
            'assignee_id' => ['nullable', 'integer'],
            'duration_hours' => ['required', 'integer', 'min:1'],
            'is_required' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên bước không được trống.',
            'duration_hours.min' => 'Thời hạn phải lớn hơn 0.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name ?? ''),
        ]);
    }
}
