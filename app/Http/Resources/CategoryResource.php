<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'color' => $this->color,
            'parent_id' => $this->parent_id,
            'normal_amount' => $this->normal_amount,
            'normal_tolerance' => $this->normal_tolerance,
            'month_total' => $this->when($this->resource->hasAttribute('month_total'), fn () => $this->resource->getAttribute('month_total')),
            'norm' => $this->when($this->resource->hasAttribute('norm'), fn () => $this->resource->getAttribute('norm')),
            'transactions_count' => $this->whenCounted('transactions'),
        ];
    }
}
