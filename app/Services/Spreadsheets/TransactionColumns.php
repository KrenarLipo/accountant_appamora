<?php

namespace App\Services\Spreadsheets;

/**
 * Column layout shared by the import template, the importer and the export, so an export can be re-imported.
 */
final class TransactionColumns
{
    /**
     * Column key => [header shown in files, accepted header spellings].
     *
     * @var array<string, array{0: string, 1: list<string>}>
     */
    public const COLUMNS = [
        'date' => ['Date', ['date', 'data', 'day']],
        'type' => ['Type', ['type', 'lloji', 'kind']],
        'category' => ['Category', ['category', 'kategoria', 'kategori']],
        'amount' => ['Amount', ['amount', 'paid', 'shuma', 'value', 'vlera']],
        'return_amount' => ['Return', ['return', 'returns', 'earned back', 'money earned back', 'kthimi']],
        'merchant' => ['Merchant', ['merchant', 'payee', 'payer', 'from/to', 'shitesi']],
        'note' => ['Note', ['note', 'notes', 'description', 'shenim', 'pershkrim']],
        'account' => ['Account', ['account', 'llogaria']],
        'to_account' => ['To account', ['to account', 'transfer to', 'ne llogarine']],
    ];

    /**
     * @return list<string>
     */
    public static function headers(): array
    {
        return array_values(array_map(fn (array $column) => $column[0], self::COLUMNS));
    }

    public static function keyFor(string $header): ?string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $header) ?? ''));

        foreach (self::COLUMNS as $key => [$label, $aliases]) {
            if ($normalized === mb_strtolower($label) || in_array($normalized, $aliases, true)) {
                return $key;
            }
        }

        return null;
    }
}
