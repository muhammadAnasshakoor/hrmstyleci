<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateEmployeeRequest extends FormRequest
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
            'user_id'           => 'nullable',
            'tenant_id'         => 'nullable',
            'name'              => 'required|string',
            'phone_no'          => 'nullable',
            'gender'            => 'required',
            'emirates_id'       => 'required|numeric',
            'city'              => 'required',
            'state'             => 'nullable',
            'zip_code'          => 'nullable|numeric',
            'profile_image_id'  => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'passport_image_id' => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'emirates_image_id' => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'resume_image_id'   => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'permanent_address' => 'required',
            'local_address'     => 'required',
            'nationality'       => 'required',
            'designation_id'    => 'required',
            'acount_title'      => 'required',
            'acount_no'         => 'required|numeric',
            'bank_name'         => 'required',
            'branch_name'       => 'required',
            'status'            => 'nullable|regex:/^[01]$/',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Custom response or throw an exception
        // for the validation errors
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
