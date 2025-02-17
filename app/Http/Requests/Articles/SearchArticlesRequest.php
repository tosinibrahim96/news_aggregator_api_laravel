<?php

declare(strict_types=1);

namespace App\Http\Requests\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchArticlesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['sometimes', 'string', 'max:100'],
            'source' => ['sometimes', 'string', Rule::exists('sources', 'slug')],
            'category' => ['sometimes', 'string', Rule::exists('categories', 'slug')],
            'author' => ['sometimes', 'string', 'max:100'],
            'date_from' => ['sometimes', 'date', 'before_or_equal:date_to'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'string', 'in:published_at,-published_at,title,-title'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_from.before_or_equal' => 'The start date must be before or equal to the end date',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date',
        ];
    }
}
