<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait OrdersContent
{
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function move(string $direction): void
    {
        $neighbor = $direction === 'up'
            ? static::query()->where('sort_order', '<', $this->sort_order)->orderByDesc('sort_order')->first()
            : static::query()->where('sort_order', '>', $this->sort_order)->orderBy('sort_order')->first();

        if ($neighbor === null) {
            return;
        }

        $currentOrder = $this->sort_order;

        $this->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $currentOrder]);
    }
}
