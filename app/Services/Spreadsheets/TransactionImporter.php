<?php

namespace App\Services\Spreadsheets;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Import;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Reads transactions from an .xlsx/.xls/.csv file, validates every row against the workspace,
 * and only writes valid, non-duplicate rows.
 */
class TransactionImporter
{
    public const MAX_ROWS = 5000;

    /**
     * @return array{rows: list<array<string, mixed>>, summary: array{total: int, ready: int, duplicates: int, errors: int, new_categories: list<string>}, missing_columns: list<string>}
     */
    public function preview(Workspace $workspace, string $path, bool $canCreateCategories): array
    {
        [$rows, $missing] = $this->readRows($path);

        if ($missing !== []) {
            return ['rows' => [], 'summary' => $this->summarize([]), 'missing_columns' => $missing];
        }

        $accounts = $workspace->accounts()->orderBy('id')->get();
        $categories = $workspace->categories()->get();
        $validated = [];

        foreach ($rows as $line => $row) {
            $validated[] = $this->validateRow($workspace, $line, $row, $accounts, $categories, $canCreateCategories);
        }

        return ['rows' => $validated, 'summary' => $this->summarize($validated), 'missing_columns' => []];
    }

    /**
     * @return array{imported: int, skipped: int, months: list<string>}
     */
    public function import(Workspace $workspace, User $user, string $path, string $filename, bool $canCreateCategories): array
    {
        $preview = $this->preview($workspace, $path, $canCreateCategories);
        $ready = array_values(array_filter($preview['rows'], fn (array $row) => $row['status'] === 'ok'));

        return DB::transaction(function () use ($workspace, $user, $path, $filename, $ready, $preview): array {
            $createdCategories = [];
            $months = [];

            foreach ($ready as $row) {
                $categoryId = $row['category_id'];

                if ($categoryId === null && $row['category'] !== null && $row['type'] !== TransactionType::Transfer->value) {
                    $key = $row['type'].'|'.mb_strtolower($row['category']);
                    $createdCategories[$key] ??= $workspace->categories()->create([
                        'name' => $row['category'],
                        'type' => TransactionType::from($row['type'])->categoryType(),
                        'color' => '#64748b',
                    ])->id;
                    $categoryId = $createdCategories[$key];
                }

                $transaction = new Transaction([
                    'type' => $row['type'],
                    'account_id' => $row['account_id'],
                    'transfer_account_id' => $row['to_account_id'],
                    'category_id' => $categoryId,
                    'amount' => $row['amount'],
                    'return_amount' => $row['return_amount'],
                    'transaction_date' => $row['date'],
                    'merchant' => $row['merchant'],
                    'description' => $row['note'],
                ]);
                $transaction->workspace()->associate($workspace);
                $transaction->currency = $workspace->base_currency;
                $transaction->created_by = $user->id;
                $transaction->save();

                $months[substr($row['date'], 0, 7)] = true;
            }

            $skipped = count($preview['rows']) - count($ready);

            $workspace->imports()->create([
                'user_id' => $user->id,
                'filename' => mb_substr($filename, 0, 255),
                'checksum' => hash_file('sha256', $path),
                'rows_imported' => count($ready),
                'rows_skipped' => $skipped,
            ]);

            return ['imported' => count($ready), 'skipped' => $skipped, 'months' => array_keys($months)];
        });
    }

    public function alreadyImported(Workspace $workspace, string $path): ?Import
    {
        return $workspace->imports()->where('checksum', hash_file('sha256', $path))->latest('id')->first();
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: list<string>}
     */
    private function readRows(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getSheet(0);
        } catch (Throwable) {
            return [[], ['The file could not be read. Save it as .xlsx or .csv and try again.']];
        }

        $data = $sheet->toArray(null, true, false, false);
        $headerRow = array_shift($data) ?? [];

        $map = [];
        foreach ($headerRow as $index => $header) {
            $key = is_string($header) ? TransactionColumns::keyFor($header) : null;
            if ($key !== null && ! isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        $missing = array_values(array_diff(['date', 'amount'], array_keys($map)));
        if ($missing !== []) {
            return [[], array_map(fn (string $key) => TransactionColumns::COLUMNS[$key][0], $missing)];
        }

        $rows = [];
        foreach (array_slice($data, 0, self::MAX_ROWS, true) as $offset => $cells) {
            $row = [];
            foreach ($map as $key => $index) {
                $value = $cells[$index] ?? null;
                $row[$key] = is_string($value) ? trim($value) : $value;
            }

            if (array_filter($row, fn ($value) => $value !== null && $value !== '') === []) {
                continue;
            }

            $rows[$offset + 2] = $row;
        }

        return [$rows, []];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<int, FinancialAccount>  $accounts
     * @param  Collection<int, Category>  $categories
     * @return array<string, mixed>
     */
    private function validateRow(Workspace $workspace, int $line, array $row, Collection $accounts, Collection $categories, bool $canCreateCategories): array
    {
        $errors = [];

        $date = $this->parseDate($row['date'] ?? null);
        if ($date === null) {
            $errors[] = 'Date is missing or not a valid date (use YYYY-MM-DD).';
        }

        $amount = $this->parseAmount($row['amount'] ?? null);
        $type = $this->parseType($row['type'] ?? null, $amount);

        if ($amount === null || (float) $amount == 0.0) {
            $errors[] = 'Amount is missing or zero.';
        }
        if ($type === null) {
            $errors[] = 'Type must be expense, income or transfer.';
        }

        $amount = $amount === null ? null : ltrim($amount, '-');

        $returnAmount = $this->parseAmount($row['return_amount'] ?? null);
        if ($returnAmount !== null) {
            $returnAmount = ltrim($returnAmount, '-');
            if ($type !== TransactionType::Expense) {
                $errors[] = 'Only expenses can have a return amount.';
            }
        }

        $account = $this->findAccount($accounts, $row['account'] ?? null);
        if ($account === null) {
            $errors[] = 'Account "'.$row['account'].'" does not exist in this workspace.';
        }

        $toAccount = null;
        if ($type === TransactionType::Transfer) {
            $toAccount = filled($row['to_account'] ?? null) ? $this->findAccount($accounts, $row['to_account']) : null;
            if ($toAccount === null || $toAccount->id === $account?->id) {
                $errors[] = 'Transfers need a different, existing "To account".';
            }
        }

        $categoryName = filled($row['category'] ?? null) ? mb_substr((string) $row['category'], 0, 100) : null;
        $category = null;
        $createsCategory = false;
        if ($categoryName !== null && $type !== null && $type !== TransactionType::Transfer) {
            $category = $categories->first(fn (Category $item) => $item->type === $type->categoryType()
                && mb_strtolower($item->name) === mb_strtolower($categoryName));

            if ($category === null) {
                $createsCategory = true;
                if (! $canCreateCategories) {
                    $errors[] = 'Category "'.$categoryName.'" does not exist and your role cannot create categories.';
                }
            }
        }

        $merchant = filled($row['merchant'] ?? null) ? mb_substr((string) $row['merchant'], 0, 255) : null;
        $note = filled($row['note'] ?? null) ? mb_substr((string) $row['note'], 0, 2000) : null;

        $status = $errors === [] ? 'ok' : 'error';

        if ($status === 'ok' && $this->isDuplicate($workspace, $date, $type, $amount, $account, $merchant)) {
            $status = 'duplicate';
        }

        return [
            'line' => $line,
            'status' => $status,
            'errors' => $errors,
            'date' => $date,
            'type' => $type?->value,
            'category' => $categoryName,
            'category_id' => $category?->id,
            'creates_category' => $createsCategory,
            'amount' => $amount,
            'return_amount' => $returnAmount,
            'merchant' => $merchant,
            'note' => $note,
            'account' => $account?->name,
            'account_id' => $account?->id,
            'to_account' => $toAccount?->name,
            'to_account_id' => $toAccount?->id,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{total: int, ready: int, duplicates: int, errors: int, new_categories: list<string>}
     */
    private function summarize(array $rows): array
    {
        $byStatus = array_count_values(array_column($rows, 'status'));
        $newCategories = collect($rows)
            ->filter(fn (array $row) => $row['creates_category'] && $row['status'] === 'ok')
            ->pluck('category')
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values()
            ->all();

        return [
            'total' => count($rows),
            'ready' => $byStatus['ok'] ?? 0,
            'duplicates' => $byStatus['duplicate'] ?? 0,
            'errors' => $byStatus['error'] ?? 0,
            'new_categories' => $newCategories,
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $serial = (float) $value;
            if ($serial < 1 || $serial > 2958465) {
                return null;
            }

            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject($serial))->toDateString();
        }

        foreach (['Y-m-d', 'd/m/Y', 'd.m.Y', 'd-m-Y', 'Y/m/d', 'd/m/y', 'd.m.y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, (string) $value);
            if ($date !== false && $date->format($format) === (string) $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Accepts 1234.5, "1,234.50", "1.234,50", "€ 12" and "-30"; returns a decimal string with 2 places.
     */
    private function parseAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $clean = preg_replace('/[^\d,.\-]/', '', (string) $value) ?? '';
        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
            $clean = str_replace(['.', ','], ['', '.'], $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        return is_numeric($clean) ? number_format((float) $clean, 2, '.', '') : null;
    }

    private function parseType(mixed $value, ?string $amount): ?TransactionType
    {
        $normalized = mb_strtolower(trim((string) $value));

        if ($normalized === '') {
            if ($amount === null) {
                return null;
            }

            return str_starts_with($amount, '-') ? TransactionType::Expense : TransactionType::Income;
        }

        return match ($normalized) {
            'expense', 'expenses', 'shpenzim', 'out', 'cost' => TransactionType::Expense,
            'income', 'te ardhura', 'in', 'revenue', 'sale' => TransactionType::Income,
            'transfer', 'transferte' => TransactionType::Transfer,
            default => null,
        };
    }

    /**
     * @param  Collection<int, FinancialAccount>  $accounts
     */
    private function findAccount(Collection $accounts, mixed $name): ?FinancialAccount
    {
        if (blank($name)) {
            return $accounts->firstWhere('is_active', true) ?? $accounts->first();
        }

        return $accounts->first(fn (FinancialAccount $account) => mb_strtolower($account->name) === mb_strtolower(trim((string) $name)));
    }

    private function isDuplicate(Workspace $workspace, ?string $date, ?TransactionType $type, ?string $amount, ?FinancialAccount $account, ?string $merchant): bool
    {
        if ($date === null || $type === null || $amount === null || $account === null) {
            return false;
        }

        return $workspace->transactions()
            ->where('transaction_date', $date)
            ->where('type', $type)
            ->where('amount', $amount)
            ->where('account_id', $account->id)
            ->where('merchant', $merchant)
            ->exists();
    }
}
