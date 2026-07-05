<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationIssueRequest extends FormRequest
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
        /** @var \App\Models\Publication $publication */
        $publication = $this->route('publication');

        return [
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('publication_issues', 'label')->where('publication_id', $publication->id),
            ],
        ];
    }
}
