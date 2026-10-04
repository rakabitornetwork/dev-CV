<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'headline',
    'summary',
    'bio',
    'photo_path',
    'location',
    'email',
    'phone',
    'availability_label',
    'seo_title',
    'seo_description',
    'cv_pdf_path',
])]
class Profile extends Model
{
    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'headline' => $this->headline,
            'summary' => $this->summary,
            'bio' => $this->bio,
            'photo_url' => Media::url($this->photo_path),
            'location' => $this->location,
            'email' => $this->email,
            'phone' => $this->phone,
            'availability_label' => $this->availability_label,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'cv_url' => route('cv.download'),
        ];
    }
}
