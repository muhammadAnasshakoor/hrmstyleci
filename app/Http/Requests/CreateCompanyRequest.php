<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateCompanyRequest extends FormRequest
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
            'branch'            => 'nullable',
            'address'           => 'nullable',
            'phone_no'          => 'nullable',
            'status'            => 'nullable|regex:/^[01]$/',
            'logo_media_id'     => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'document_media_id' => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'country'           => 'required',
            'city'              => 'required',
            'state'             => 'nullable',
            'zip_code'          => 'nullable|numeric',
            'registration_no'   => 'nullable|numeric',
            'note'              => 'nullable',
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
