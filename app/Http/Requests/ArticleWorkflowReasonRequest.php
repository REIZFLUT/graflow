<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleWorkflowReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Article $article */
        $article = $this->route('article');

        $ability = match ($this->route()?->getName()) {
            'articles.workflow.submit-manuscript' => 'submitManuscript',
            'articles.workflow.complete-editorial-work' => 'completeEditorialWork',
            'articles.workflow.request-revision' => 'requestRevision',
            default => 'manageWorkflow',
        };

        return $this->user()?->can($ability, $article) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => [
                Rule::requiredIf($this->route()?->getName() === 'articles.workflow.request-revision'),
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
