<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateDutyRequest extends FormRequest
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
            'user_id'      => 'nullable',
            'tenant_id'    => 'nullable',
            'employee_id'  => 'required|numeric',
            'company_id'   => 'required|numeric',
            'policy_id'    => 'required|numeric',
            'note'         => 'nullable',
            'joining_date' => 'required',
            'ended_at'     => 'nullable',
            'status'       => 'nullable|regex:/^[01]$/',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Custom response or throw an exception
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
