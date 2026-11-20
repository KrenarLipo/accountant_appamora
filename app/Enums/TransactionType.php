<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function categoryType(): ?CategoryType
    {
        return match ($this) {
            self::Income => CategoryType::Income,
            self::Expense => CategoryType::Expense,
            self::Transfer => null,
        };
    }
}
