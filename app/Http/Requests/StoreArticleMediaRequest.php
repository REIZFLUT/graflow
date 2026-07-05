<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreArticleMediaRequest extends FormRequest
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
        $maxSize = config('article-media.max_upload_size');

        $rules = [
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'gif'])
                    ->max($maxSize),
            ],
            'alt_text' => ['required', 'string', 'max:255'],
            'copyright' => ['required', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->route('article') === null) {
            $rules['staging_token'] = ['required', 'uuid'];
        }

        return $rules;
    }
}
