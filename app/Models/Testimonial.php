<?php

namespace App\Models;

use App\Models\Concerns\OrdersContent;
use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'role',
    'company',
    'quote',
    'avatar_path',
    'is_visible',
    'sort_order',
])]
class Testimonial extends Model
{
    use OrdersContent;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
            'name' => $this->name,
            'role' => $this->role,
            'company' => $this->company,
            'quote' => $this->quote,
            'avatar_url' => Media::url($this->avatar_path),
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
