<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\Response;

class UserInfoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'avatar' => 'nullable',
            'phone' => [
                'nullable',
                'regex:/^(\+84|0)(3|5|7|8|9)([0-9]{8})$/'
            ],
            'gender' => [
                'nullable',
                Rule::in('male', 'female')
            ],
            'birth_date' => 'nullable|date',
        ];
    }

    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'message' => 'Input data is incorrect!'
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
