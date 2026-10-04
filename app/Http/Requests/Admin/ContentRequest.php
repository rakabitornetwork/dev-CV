<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['end_date', 'end_year', 'location', 'url', 'company', 'category', 'field', 'description'] as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
        }

        if (is_string($this->input('tech_stack'))) {
            $merge['tech_stack'] = array_values(array_filter(array_map(
                trim(...),
                explode(',', $this->input('tech_stack')),
            )));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $link = ['required', 'string', 'max:255', 'regex:/^(https?:\/\/|mailto:|tel:)/i'];

        return match ($this->route('type')) {
            'social-links' => [
                'label' => ['required', 'string', 'max:80'],
                'icon' => ['required', Rule::in(self::icons())],
                'url' => $link,
                'is_visible' => ['sometimes', 'boolean'],
            ],
            'experiences' => [
                'role' => ['required', 'string', 'max:120'],
                'company' => ['required', 'string', 'max:120'],
                'location' => ['nullable', 'string', 'max:120'],
                'start_date' => ['required', 'date_format:Y-m'],
                'end_date' => ['nullable', 'date_format:Y-m'],
                'is_current' => ['sometimes', 'boolean'],
                'description' => ['required', 'string', 'max:4000'],
                'is_visible' => ['sometimes', 'boolean'],
            ],
            'educations' => [
                'school' => ['required', 'string', 'max:160'],
                'degree' => ['required', 'string', 'max:160'],
                'field' => ['nullable', 'string', 'max:160'],
                'start_year' => ['required', 'integer', 'min:1950', 'max:2100'],
                'end_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
                'description' => ['nullable', 'string', 'max:4000'],
                'is_visible' => ['sometimes', 'boolean'],
            ],
            'skills' => [
                'name' => ['required', 'string', 'max:80'],
                'level' => ['required', 'integer', 'min:0', 'max:100'],
                'category' => ['nullable', 'string', 'max:80'],
                'is_visible' => ['sometimes', 'boolean'],
            ],
            'projects' => [
                'title' => ['required', 'string', 'max:160'],
                'summary' => ['required', 'string', 'max:2000'],
                'url' => ['nullable', 'string', 'max:255', 'regex:/^https?:\/\/.+/i'],
                'tech_stack' => ['nullable', 'array'],
                'tech_stack.*' => ['string', 'max:40'],
                'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
                'is_visible' => ['sometimes', 'boolean'],
            ],
            'testimonials' => [
                'name' => ['required', 'string', 'max:120'],
                'role' => ['required', 'string', 'max:120'],
                'company' => ['nullable', 'string', 'max:120'],
                'quote' => ['required', 'string', 'max:2000'],
                'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'is_visible' => ['sometimes', 'boolean'],
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function icons(): array
    {
        return [
            'Github',
            'Linkedin',
            'Instagram',
            'Youtube',
            'Dribbble',
            'Figma',
            'Mail',
            'Phone',
            'Globe',
            'Link',
        ];
    }
}
