<?php

namespace App\Http\Requests;

use App\Models\Publication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Publication $publication */
        $publication = $this->route('publication');

        return $this->user()?->can('update', $publication) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Publication $publication */
        $publication = $this->route('publication');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('publications', 'name')
                    ->where('owner_id', $this->user()->id)
                    ->ignore($publication->id),
            ],
            'editor_settings_set_id' => [
                Rule::prohibitedIf(fn () => ! $this->user()->canManageEditorSettingsSets()),
                'nullable',
                'integer',
                Rule::exists('editor_settings_sets', 'id')->where('owner_id', $this->user()->id),
            ],
        ];
    }
}
