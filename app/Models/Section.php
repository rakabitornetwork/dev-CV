<?php

namespace App\Models;

use App\Models\Concerns\OrdersContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'title', 'is_visible', 'sort_order'])]
class Section extends Model
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
            'key' => $this->key,
            'title' => $this->title,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
