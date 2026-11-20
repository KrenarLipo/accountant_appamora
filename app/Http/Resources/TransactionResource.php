<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'return_amount' => $this->return_amount,
            'profit' => $this->return_amount === null
                ? null
                : (string) BigDecimal::of($this->return_amount)->minus($this->amount)->toScale(2, RoundingMode::HalfUp),
            'currency' => $this->currency,
            'transaction_date' => $this->transaction_date->toDateString(),
            'merchant' => $this->merchant,
            'description' => $this->description,
            'account' => $this->whenLoaded('account', fn () => ['id' => $this->account->id, 'name' => $this->account->name]),
            'transfer_account' => $this->whenLoaded('transferAccount', fn () => $this->transferAccount
                ? ['id' => $this->transferAccount->id, 'name' => $this->transferAccount->name]
                : null),
            'category' => $this->whenLoaded('category', fn () => $this->category
                ? ['id' => $this->category->id, 'name' => $this->category->name, 'color' => $this->category->color]
                : null),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
