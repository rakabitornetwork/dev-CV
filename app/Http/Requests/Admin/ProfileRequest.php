<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['location', 'email', 'phone', 'availability_label', 'seo_title', 'seo_description'] as $key) {
            if ($this->exists($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'headline' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:600'],
            'bio' => ['required', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'availability_label' => ['nullable', 'string', 'max:120'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cv_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }
}
