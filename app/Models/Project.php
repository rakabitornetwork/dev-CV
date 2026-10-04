<?php

namespace App\Models;

use App\Models\Concerns\OrdersContent;
use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'summary',
    'url',
    'tech_stack',
    'cover_path',
    'is_visible',
    'sort_order',
])]
class Project extends Model
{
    use OrdersContent;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tech_stack' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'url' => $this->url,
            'tech_stack' => array_values($this->tech_stack ?? []),
            'cover_url' => Media::url($this->cover_path),
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
