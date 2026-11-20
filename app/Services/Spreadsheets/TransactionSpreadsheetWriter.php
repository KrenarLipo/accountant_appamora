<?php

namespace App\Services\Spreadsheets;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Services\CategoryNormMonitor;
use App\Services\FinancialSummary;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Writes the import template and transaction exports. Every file states its currency and period.
 */
class TransactionSpreadsheetWriter
{
    private const MONEY_FORMAT = '#,##0.00';

    public function __construct(
        private FinancialSummary $summary,
        private CategoryNormMonitor $normMonitor,
    ) {}

    /**
     * @return string path of a temporary .xlsx file
     */
    public function template(Workspace $workspace): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Transactions');
        $this->writeHeader($sheet, TransactionColumns::headers());
        $sheet->getStyle('A2:A5001')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle('D2:E5001')->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

        $lists = $spreadsheet->createSheet()->setTitle('Lists');
        $lists->fromArray([['Types', 'Expense categories', 'Income categories', 'Accounts']]);
        $columns = [
            'A' => array_map(fn (TransactionType $type) => $type->value, TransactionType::cases()),
            'B' => $workspace->categories()->where('type', CategoryType::Expense)->orderBy('name')->pluck('name')->all(),
            'C' => $workspace->categories()->where('type', CategoryType::Income)->orderBy('name')->pluck('name')->all(),
            'D' => $workspace->accounts()->where('is_active', true)->orderBy('name')->pluck('name')->all(),
        ];
        foreach ($columns as $column => $values) {
            foreach (array_values($values) as $index => $value) {
                $lists->setCellValueExplicit($column.($index + 2), $value, DataType::TYPE_STRING);
            }
        }
        $lists->getStyle('A1:D1')->getFont()->setBold(true);

        $this->addListValidation($sheet, 'B2:B5001', 'Lists!$A$2:$A$4');
        $this->addListValidation($sheet, 'H2:H5001', 'Lists!$D$2:$D$'.max(2, count($columns['D']) + 1));
        $this->addListValidation($sheet, 'I2:I5001', 'Lists!$D$2:$D$'.max(2, count($columns['D']) + 1));

        $help = $spreadsheet->createSheet()->setTitle('How to fill');
        $help->fromArray([
            ['How to fill the Transactions sheet'],
            [''],
            ['Column', 'What to write', 'Example'],
            ['Date', 'Day of the transaction (YYYY-MM-DD, or an Excel date)', '2026-11-03'],
            ['Type', 'expense, income or transfer. Leave empty to use the sign of Amount (negative = expense).', 'expense'],
            ['Category', 'A name from the Lists sheet. New names are created when you import (if your role allows).', 'Groceries'],
            ['Amount', 'What you paid or received. Use a dot or comma for decimals.', '45.90'],
            ['Return', 'Expenses only: money you earned back from this expense (for profit/loss).', '60.00'],
            ['Merchant', 'Shop, supplier, client or payer', 'Conad'],
            ['Note', 'Anything else (optional)', 'Weekly shopping'],
            ['Account', 'An account from the Lists sheet. Empty = your main account.', $columns['D'][0] ?? 'Main account'],
            ['To account', 'Transfers only: the account receiving the money', ''],
            [''],
            ['Currency: '.$workspace->base_currency.'. Rows that already exist (same date, type, amount, account and merchant) are skipped.'],
        ]);
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $help->getStyle('A3:C3')->getFont()->setBold(true);
        foreach (['A' => 14, 'B' => 90, 'C' => 18] as $column => $width) {
            $help->getColumnDimension($column)->setWidth($width);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $this->save($spreadsheet, 'xlsx');
    }

    /**
     * @param  array{type?: string|null, account_id?: int|string|null, category_id?: int|string|null}  $filters
     * @return string path of a temporary file
     */
    public function export(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to, array $filters, string $format): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Transactions');
        $headers = [...TransactionColumns::headers(), 'Profit/loss', 'Currency', 'Recorded by'];
        $this->writeHeader($sheet, $headers);

        $row = 2;
        $this->query($workspace, $from, $to, $filters)
            ->with(['account', 'transferAccount', 'category', 'creator'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->chunk(500, function ($transactions) use ($sheet, &$row): void {
                foreach ($transactions as $transaction) {
                    /** @var Transaction $transaction */
                    $profit = $transaction->return_amount !== null
                        ? (float) (string) BigDecimal::of($transaction->return_amount)->minus($transaction->amount)
                        : null;

                    $sheet->fromArray([[
                        $transaction->transaction_date->toDateString(),
                        $transaction->type->value,
                        $transaction->category?->name,
                        (float) $transaction->amount,
                        $transaction->return_amount !== null ? (float) $transaction->return_amount : null,
                        $transaction->merchant,
                        $transaction->description,
                        $transaction->account?->name,
                        $transaction->transferAccount?->name,
                        $profit,
                        $transaction->currency,
                        $transaction->creator?->name,
                    ]], null, 'A'.$row, true);
                    $row++;
                }
            });

        $lastRow = max(2, $row - 1);
        $sheet->getStyle('D2:E'.$lastRow)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $sheet->getStyle('J2:J'.$lastRow)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);

        if ($format === 'xlsx') {
            $this->writeSummarySheet($spreadsheet->createSheet(), $workspace, $from, $to);
            $spreadsheet->setActiveSheetIndex(0);
        }

        return $this->save($spreadsheet, $format);
    }

    /**
     * @param  array{type?: string|null, account_id?: int|string|null, category_id?: int|string|null}  $filters
     * @return Builder<Transaction>
     */
    private function query(Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to, array $filters): Builder
    {
        return $workspace->transactions()
            ->getQuery()
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['account_id'] ?? null, fn ($query, $accountId) => $query->where(
                fn ($inner) => $inner->where('account_id', $accountId)->orWhere('transfer_account_id', $accountId)
            ))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId));
    }

    private function writeSummarySheet(Worksheet $sheet, Workspace $workspace, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $sheet->setTitle('Summary');
        $totals = $this->summary->totals($workspace, $from, $to);

        $sheet->fromArray([
            ['Accountant by AppAmora — summary'],
            ['Workspace', $workspace->name],
            ['Currency', $workspace->base_currency],
            ['Period', $from->toDateString().' to '.$to->toDateString()],
            ['Basis', 'Cash basis. Transfers between your accounts are not counted.'],
            [''],
            ['Income', (float) $totals['income']],
            ['Expenses', (float) $totals['expenses']],
            ['Money earned back (returns)', (float) $totals['returns']],
            ['Net result (profit / loss)', (float) $totals['net']],
            [''],
            ['Category', 'Type', 'Total', 'Normal per month', 'Difference %'],
        ], null, 'A1', true);

        $categoryTotals = $this->summary->categoryTotals($workspace, $from, $to);
        $singleMonth = $from->format('Y-m') === $to->format('Y-m');
        $norms = $singleMonth ? collect($this->normMonitor->statuses($workspace, $from))->keyBy('category_id') : collect();

        $row = 13;
        foreach ($workspace->categories()->orderBy('type')->orderBy('name')->get() as $category) {
            if (! isset($categoryTotals[$category->id]) && $category->normal_amount === null) {
                continue;
            }

            $sheet->fromArray([[
                $category->name,
                $category->type->value,
                (float) ($categoryTotals[$category->id] ?? 0),
                $category->normal_amount !== null ? (float) $category->normal_amount : null,
                $norms->get($category->id)['deviation_percent'] ?? null,
            ]], null, 'A'.$row, true);
            $row++;
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A7:A10')->getFont()->setBold(true);
        $sheet->getStyle('A12:E12')->getFont()->setBold(true);
        $sheet->getStyle('B7:B10')->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $sheet->getStyle('C13:D'.max(13, $row - 1))->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(24);
        foreach (['C', 'D', 'E'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(16);
        }
    }

    /**
     * @param  list<string>  $headers
     */
    private function writeHeader(Worksheet $sheet, array $headers): void
    {
        $sheet->fromArray([$headers]);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $style = $sheet->getStyle('A1:'.$lastColumn.'1');
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('172440');
        $sheet->freezePane('A2');

        foreach (range(1, count($headers)) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth($index === 7 ? 30 : 16);
        }
    }

    private function addListValidation(Worksheet $sheet, string $range, string $source): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setFormula1($source);
        $sheet->setDataValidation($range, $validation);
    }

    private function save(Spreadsheet $spreadsheet, string $format): string
    {
        $base = tempnam(sys_get_temp_dir(), 'acct');
        @unlink($base);
        $path = $base.'.'.$format;
        $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
