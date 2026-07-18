<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SpellCheckRequest extends FormRequest
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
            'text' => ['required', 'string', 'max:40000'],
            'language' => ['sometimes', 'string', 'max:20'],
            'level' => ['sometimes', 'string', 'in:default,picky'],
        ];
    }

    /**
     * @return array{text: string, language: string, level: string}
     */
    public function payload(): array
    {
        /** @var array{text: string, language?: string, level?: string} $validated */
        $validated = $this->validated();

        return [
            'text' => $validated['text'],
            'language' => $validated['language'] ?? 'de-DE',
            'level' => $validated['level'] ?? 'picky',
        ];
    }
}
