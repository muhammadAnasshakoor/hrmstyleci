<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateTenantRequest extends FormRequest
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
            'user_id'           => 'nullable', // nullable so can be given within the controller
            'name'              => 'required|string',
            'phone_no'          => 'required',
            'website'           => 'nullable',
            'address'           => 'nullable',
            'city'              => 'nullable',
            'state'             => 'nullable',
            'logo_media_id'     => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'document_media_id' => 'nullable|mimes:jpeg,png,gif,svg+xml,image/jpeg,image/pjpeg,image/png,image/gif,pdf,image/webp',
            'zip_code'          => 'nullable|numeric',
            'country'           => 'required',
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
