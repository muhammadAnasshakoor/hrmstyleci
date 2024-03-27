<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class CreatePolicyRequest extends FormRequest
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
            'tenant_id' => 'nullable',
            'user_id' => 'nullable',
            'name' => 'required|string',
            'shift_start' => 'required',
            'shift_end' => 'required',
            'late_allow' => 'required',
            'early_departure_allow' => 'required',
            'late_deduction' => 'required',
            'early_deduction' => 'required',
            'status' => 'nullable|regex:/^[01]$/',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        // Custom response or throw an exception
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
