<?php

namespace App\Http\Requests\Step;

use Illuminate\Foundation\Http\FormRequest;

class ReorderStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('step_definition')->processTemplate);
    }

    public function rules(): array
    {
        return [
            'new_order' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_order.required' => 'Thứ tự mới không được trống.',
            'new_order.min' => 'Thứ tự phải lớn hơn 0.',
        ];
    }
}
