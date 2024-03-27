<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeTransferRequest extends FormRequest
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
            'employee_id' => 'nullable',
            'from_company_id' => 'nullable',
            'to_company_id' => 'nullable',
            'from_duty_id' => 'nullable',
            'to_duty_id' => 'nullable',
            'started_at' => 'nullable',
            'ended_at' => 'nullable',
            'reason' => 'nullable',
        ];
    }
    
    protected function failedValidation(Validator $validator)
    {
        // Custom response or throw an exception
        // for the validation errors
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
