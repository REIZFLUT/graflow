<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Support\Handbook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHandbookArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $issue = Handbook::resolveIssue();

        return [
            'title' => ['required', 'string', 'max:255'],
            'publication_chapter_id' => [
                'nullable',
                'integer',
                Rule::exists('publication_chapters', 'id')
                    ->where('publication_issue_id', $issue?->id),
            ],
        ];
    }
}
