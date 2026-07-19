<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Article::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:title,status,publication,assignee,deadline,updated_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'publication_id' => ['nullable', 'integer', 'exists:publications,id'],
            'issue_id' => ['nullable', 'integer', 'exists:publication_issues,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ];
    }
}
