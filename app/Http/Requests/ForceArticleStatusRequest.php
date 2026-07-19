<?php

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForceArticleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Article $article */
        $article = $this->route('article');

        return $this->user()?->can('forceStatus', $article) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
