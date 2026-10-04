<?php

namespace App\Models;

use App\Models\Concerns\OrdersContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'role',
    'company',
    'location',
    'start_date',
    'end_date',
    'is_current',
    'description',
    'is_visible',
    'sort_order',
])]
class Experience extends Model
{
    use OrdersContent;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
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
            'role' => $this->role,
            'company' => $this->company,
            'location' => $this->location,
            'start_date' => $this->start_date?->format('Y-m'),
            'end_date' => $this->end_date?->format('Y-m'),
            'is_current' => $this->is_current,
            'description' => $this->description,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
