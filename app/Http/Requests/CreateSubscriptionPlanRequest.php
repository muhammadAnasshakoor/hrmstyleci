<?php

namespace App\Http\Requests;

use App\Traits\ValidationErrorTrait;
use Illuminate\Foundation\Http\FormRequest; // Import the trait correctly

class CreateSubscriptionPlanRequest extends FormRequest
{
    use ValidationErrorTrait; // Use the trait correctly

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
            'user_id'          => 'nullable',
            'title'            => 'required',
            'price'            => 'required',
            'discounted_price' => 'required',
            'description'      => 'nullable',
            'status'           => 'nullable|regex:/^[01]$/',
        ];
    }
}
