<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class QaRequest extends FormRequest
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
    public function rules(): array
    {
        if ($this->method() == "POST"){
            return [
                'title' => 'required|string',
                'content_qa' => 'required|string',
                'tag' => 'required|string'
            ];
        }

        return [
            'title' => 'nullable|string',
            'order_by_created_at' => [
                'nullable',
                Rule::in(["asc", "desc"])
            ],
        ];

    }

    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(response()->json([
            'message' => 'Input data is incorrect!'
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
