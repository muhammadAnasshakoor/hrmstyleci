<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateAttendanceRequest extends FormRequest
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
            'employee_id'        => 'nullable',
            'tenant_id'          => 'nullable',
            'company_id'         => 'nullable',
            'check_in'           => 'nullable|date_format:h:i A',
            'check_out'          => 'nullable|date_format:h:i A',
            'date'               => 'nullable',
            'check_in_location'  => 'nullable',
            'check_out_location' => 'nullable',
            'total_hours'        => 'nullable',
            'type'               => 'nullable|in:present,absent,late,leave',
            'reason'             => 'nullable',
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
