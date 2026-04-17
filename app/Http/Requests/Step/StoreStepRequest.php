<?php

namespace App\Http\Requests\Step;

use App\Models\ProcessTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StoreStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = ProcessTemplate::findOrFail($this->input('template_id'));

        return $this->user()->can('update', $template);
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:process_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_type' => ['nullable', 'in:user,role,department'],
            'assignee_id' => ['nullable', 'required_if:assignee_type,!=,null', 'integer'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'is_required' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên bước không được trống.',
            'duration_hours.min' => 'Thời hạn phải lớn hơn 0.',
            'assignee_type.in' => 'Loại người phụ trách không hợp lệ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name ?? ''),
        ]);
    }
}
