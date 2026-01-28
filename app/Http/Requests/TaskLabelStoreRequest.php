<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskLabelStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ];
    }

    /**
     * Get custom error messages for validator failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a label name.',
            'name.max' => 'Label names must be 50 characters or fewer.',
            'color.required' => 'Please provide a label color.',
            'color.regex' => 'Label colors must be a valid hex value.',
        ];
    }
}
