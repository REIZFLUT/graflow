<?php

namespace App\Http\Requests;

use App\Models\Publication;
use App\Models\PublicationIssue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicationChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Publication $publication */
        $publication = $this->route('publication');

        return $this->user()?->can('update', $publication) ?? false;
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
            'position' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('publication_chapters', 'position')
                    ->where('publication_issue_id', $issue->id),
            ],
        ];
    }
}
