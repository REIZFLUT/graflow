<?php

namespace App\Http\Requests;

use App\Enums\PublicationEditorFont;
use App\Models\EditorSettingsSet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditorSettingsSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EditorSettingsSet $editorSettingsSet */
        $editorSettingsSet = $this->route('editor_settings_set');

        return $this->user()?->can('update', $editorSettingsSet) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var EditorSettingsSet $editorSettingsSet */
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
