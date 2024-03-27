<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\ValidationErrorTrait;

class CreateSubscriberRequest extends FormRequest
{
    use ValidationErrorTrait;
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
            'tenant_id' => 'required',
            'subscription_plan_id' => 'required',
            'user_id' => 'nullable',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'amount' => 'nullable',
            'status' => 'nullable|regex:/^[01]$/',        ];
    }
}
