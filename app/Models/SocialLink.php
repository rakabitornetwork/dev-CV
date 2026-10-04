<?php

namespace App\Models;

use App\Models\Concerns\OrdersContent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'icon', 'url', 'is_visible', 'sort_order'])]
class SocialLink extends Model
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
            'label' => $this->label,
            'icon' => $this->icon,
            'url' => $this->url,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
