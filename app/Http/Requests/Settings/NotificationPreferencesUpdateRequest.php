<?php

namespace App\Http\Requests\Settings;

use App\Enums\NotificationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferencesUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'preferences' => ['required', 'array'],
        ];

        foreach (NotificationType::forRole($this->user()->role) as $type) {
            $rules["preferences.{$type->value}"] = ['required', 'boolean'];
        }

        return $rules;
    }

    /**
     * The notification preferences relevant to the current user's role.
     *
     * @return array<string, bool>
     */
    public function relevantPreferences(): array
    {
        $preferences = [];

        foreach (NotificationType::forRole($this->user()->role) as $type) {
            $preferences[$type->value] = $this->boolean("preferences.{$type->value}");
        }

        return $preferences;
    }
}
