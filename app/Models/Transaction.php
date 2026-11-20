<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\Auditable;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type', 'account_id', 'transfer_account_id', 'category_id', 'amount', 'return_amount',
    'currency', 'transaction_date', 'merchant', 'description',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:4',
            'return_amount' => 'decimal:4',
            'transaction_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class)->withTrashed();
    }

    /**
     * @return BelongsTo<FinancialAccount, $this>
     */
    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'transfer_account_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
