<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagementPlanUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('management');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $limitKeys = config('plan.limit_keys', []);
        $limitRules = [];

        foreach ($limitKeys as $limitKey) {
            $limitRules["limits.{$limitKey}"] = ['nullable', 'integer', 'min:0'];
        }

        return [
            'price_monthly' => ['required', 'integer', 'min:0'],
            'limits' => ['required', 'array'],
            ...$limitRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price_monthly.required' => 'Informe o valor mensal.',
            'price_monthly.integer' => 'O valor mensal precisa ser numérico.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $limits = $this->input('limits', []);

        foreach ($limits as $key => $value) {
            if ($value === '') {
                $limits[$key] = null;
            }
        }

        $this->merge([
            'limits' => $limits,
        ]);
    }
}
