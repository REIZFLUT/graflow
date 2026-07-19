<?php

namespace App\Http\Requests;

use App\Models\Article;
use App\Models\Publication;
use App\Models\PublicationIssue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateArticleMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Article $article */
        $article = $this->route('article');

        return $this->user()?->can('manageWorkflow', $article) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userPublicationIds = Publication::query()
            ->visibleTo($this->user())
            ->pluck('id')
            ->all();

        $publicationId = null;
        $publicationIssueId = null;

        if ($this->filled('publication_issue_id')) {
            $publicationIssueId = $this->integer('publication_issue_id');
            $publicationId = PublicationIssue::query()
                ->whereKey($publicationIssueId)
                ->whereIn('publication_id', $userPublicationIds)
                ->value('publication_id');
        }

        return [
            'publication_issue_id' => [
                'nullable',
                'integer',
                Rule::exists('publication_issues', 'id')->whereIn('publication_id', $userPublicationIds),
            ],
            'publication_chapter_id' => [
                Rule::excludeIf(fn (): bool => ! $this->filled('publication_issue_id')),
                'nullable',
                'integer',
                Rule::exists('publication_chapters', 'id')
                    ->where('publication_issue_id', $publicationIssueId),
            ],
            'position' => [
                Rule::requiredIf(fn (): bool => $this->filled('publication_issue_id')),
                'integer',
                'min:1',
            ],
            'publication_category_ids' => ['nullable', 'array'],
            'publication_category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('publication_categories', 'id')->where(
                    fn ($query) => $query->where('publication_id', $publicationId),
                ),
            ],
            'editor_settings_set_id' => [
                Rule::prohibitedIf(fn () => ! $this->user()->canManageEditorSettingsSets()),
                'nullable',
                'integer',
                Rule::exists('editor_settings_sets', 'id')->where('owner_id', $this->user()->id),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('publication_issue_id')) {
                    return;
                }

                if ($this->filled('publication_category_ids')) {
                    $validator->errors()->add(
                        'publication_category_ids',
                        __('validation_custom.categories_require_publication'),
                    );
                }
            },
        ];
    }
}
