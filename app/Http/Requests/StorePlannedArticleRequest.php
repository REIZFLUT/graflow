<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Publication;
use App\Models\PublicationIssue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlannedArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Publication $publication */
        $publication = $this->route('publication');

        return $this->user()->role === UserRole::ProductManager
            && $this->user()->can('update', $publication);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PublicationIssue $issue */
        $issue = $this->route('issue');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Author->value),
            ],
            'publication_chapter_id' => [
                'nullable',
                'integer',
                Rule::exists('publication_chapters', 'id')
                    ->where('publication_issue_id', $issue->id),
            ],
            'position' => ['required', 'integer', 'min:1'],
            'submission_deadline' => ['required', 'date'],
            'target_character_count' => ['required', 'integer', 'min:1'],
        ];
    }
}
