<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('publications', 'name')->where('owner_id', $this->user()->id),
            ],
            'editor_settings_set_id' => [
                'required',
                'integer',
                Rule::exists('editor_settings_sets', 'id')->where('owner_id', $this->user()->id),
            ],
        ];
    }
}
