<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProofreadRequest extends FormRequest
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
        ];
    }

    public function text(): string
    {
        return (string) $this->validated('text');
    }

    public function language(): string
    {
        return (string) ($this->validated('language') ?? config('services.ai_lektorat.language', 'de'));
    }
}
