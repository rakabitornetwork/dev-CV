<?php

namespace App\Models;

use App\Models\Concerns\OrdersContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'school',
    'degree',
    'field',
    'start_year',
    'end_year',
    'description',
    'is_visible',
    'sort_order',
])]
class Education extends Model
{
    use OrdersContent;

    protected $table = 'educations';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
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
            'school' => $this->school,
            'degree' => $this->degree,
            'field' => $this->field,
            'start_year' => $this->start_year,
            'end_year' => $this->end_year,
            'description' => $this->description,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
