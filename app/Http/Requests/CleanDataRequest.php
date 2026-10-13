<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CleanDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'confirmation_phrase' => ['required', 'string', 'in:DELETE CRM DATA'],
            'acknowledge_risks' => ['required', 'accepted'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'confirmation_phrase.required' => 'The confirmation phrase is required.',
            'confirmation_phrase.in' => 'You must enter the exact confirmation phrase "DELETE CRM DATA".',
            'acknowledge_risks.required' => 'You must explicitly acknowledge the destructive impact of this operation.',
            'acknowledge_risks.accepted' => 'You must check the confirmation box acknowledging that this operation cannot be undone.',
            'password.required' => 'Super Administrator password re-authentication is required.',
        ];
    }
}
