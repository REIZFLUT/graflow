<?php

namespace App\Http\Requests;

use App\Enums\PublicationEditorFont;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditorSettingsSetRequest extends FormRequest
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
        /** @var \App\Models\EditorSettingsSet $editorSettingsSet */
        $editorSettingsSet = $this->route('editor_settings_set');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('editor_settings_sets', 'name')
                    ->where('owner_id', $this->user()->id)
                    ->ignore($editorSettingsSet->id),
            ],
            'font' => ['required', Rule::enum(PublicationEditorFont::class)],
            'has_marginal_column' => ['required', 'boolean'],
        ];
    }
}
