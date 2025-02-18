<?php

declare(strict_types=1);

namespace App\Http\Requests\Preferences;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sources' => ['sometimes', 'array'],
            'sources.*' => ['required', 'string', Rule::exists('sources', 'slug')],
            
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['required', 'string', Rule::exists('categories', 'slug')],
            
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['required', 'string', 'max:100'],
        ];
    }
}
