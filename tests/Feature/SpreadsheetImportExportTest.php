<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SpreadsheetImportExportTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private FinancialAccount $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-20 12:00:00');
        $this->workspace = Workspace::factory()->create();
        $this->bank = FinancialAccount::factory()->for($this->workspace)->create(['name' => 'Bank']);
        FinancialAccount::factory()->for($this->workspace)->create(['name' => 'Cash']);
        Category::factory()->for($this->workspace)->create(['name' => 'Groceries']);
    }

    public function test_the_template_lists_the_workspace_categories_and_accounts(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $response = $this->get($this->url('imports/template'))->assertOk();
        $response->assertDownload('accountant-import-template.xlsx');

        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['Date', 'Type', 'Category', 'Amount', 'Return', 'Merchant', 'Note', 'Account', 'To account'], $book->getSheetByName('Transactions')->rangeToArray('A1:I1')[0]);
        $this->assertSame('Groceries', $book->getSheetByName('Lists')->getCell('B2')->getValue());
        $this->assertSame('Bank', $book->getSheetByName('Lists')->getCell('D2')->getValue());
    }

    public function test_preview_validates_each_row_without_saving(): void
    {
        Transaction::factory()->for($this->workspace)->create([
            'account_id' => $this->bank->id, 'amount' => '12.50', 'transaction_date' => '2026-11-01', 'merchant' => 'Bakery',
        ]);
        Sanctum::actingAs($this->workspace->owner);

        $response = $this->post($this->url('imports/preview'), ['file' => $this->xlsx([
            ['2026-11-01', 'expense', 'Groceries', '12.50', '', 'Bakery', '', 'Bank', ''],
            ['03/11/2026', 'expense', 'Stock', '1.234,50', '1500', 'Wholesaler', 'Resale', '', ''],
            ['2026-11-04', 'income', 'Sales', 900, '', 'Client', '', 'Bank', ''],
            ['2026-11-05', 'expense', '', '40', '', '', '', 'Unknown account', ''],
            ['not a date', 'expense', '', '10', '', '', '', '', ''],
            ['2026-11-06', 'transfer', '', '100', '', '', '', 'Bank', 'Cash'],
        ])], ['Accept' => 'application/json'])->assertOk();

        $response->assertJsonPath('data.summary', [
            'total' => 6, 'ready' => 3, 'duplicates' => 1, 'errors' => 2, 'new_categories' => ['Stock', 'Sales'],
        ]);
        $rows = collect($response->json('data.rows'))->keyBy('line');
        $this->assertSame('duplicate', $rows[2]['status']);
        $this->assertSame(['2026-11-03', '1234.50', '1500.00', 'Bank'], [$rows[3]['date'], $rows[3]['amount'], $rows[3]['return_amount'], $rows[3]['account']]);
        $this->assertSame(['Account "Unknown account" does not exist in this workspace.'], $rows[5]['errors']);
        $this->assertSame(['Date is missing or not a valid date (use YYYY-MM-DD).'], $rows[6]['errors']);
        $this->assertSame('Cash', $rows[7]['to_account']);

        $this->assertSame(1, Transaction::count());
    }

    public function test_import_saves_valid_rows_creates_categories_and_blocks_the_same_file_twice(): void
    {
        Sanctum::actingAs($this->workspace->owner);
        $rows = [
            ['2026-11-03', 'expense', 'Stock', '300', '450', 'Wholesaler', '', 'Bank', ''],
            ['2026-11-04', 'expense', 'Groceries', '20.10', '', 'Conad', '', '', ''],
            ['bad', 'expense', '', '10', '', '', '', '', ''],
        ];

        $this->post($this->url('imports'), ['file' => $this->xlsx($rows)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.skipped', 1);

        $stock = Category::where('name', 'Stock')->sole();
        $this->assertSame('expense', $stock->type->value);
        $this->assertDatabaseHas('transactions', [
            'category_id' => $stock->id, 'amount' => '300.0000', 'return_amount' => '450.0000',
            'created_by' => $this->workspace->owner->id, 'currency' => 'EUR',
        ]);
        $this->assertDatabaseHas('imports', ['workspace_id' => $this->workspace->id, 'rows_imported' => 2, 'rows_skipped' => 1]);

        $this->post($this->url('imports'), ['file' => $this->xlsx($rows)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This exact file was already imported. Tick "import again" if you really want to add it twice.');
        $this->assertSame(2, Transaction::count());
    }

    public function test_csv_files_are_accepted(): void
    {
        Sanctum::actingAs($this->workspace->owner);
        $csv = "Date,Type,Category,Amount,Merchant\n2026-11-07,expense,Groceries,15.20,Market\n";

        $this->post($this->url('imports'), ['file' => UploadedFile::fake()->createWithContent('bank.csv', $csv)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.imported', 1);
    }

    public function test_a_file_without_date_or_amount_columns_is_explained(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->post($this->url('imports/preview'), [
            'file' => UploadedFile::fake()->createWithContent('wrong.csv', "Name,Code\nA,1\n"),
        ], ['Accept' => 'application/json'])->assertJsonPath('data.missing_columns', ['Date', 'Amount']);
    }

    public function test_a_contributor_cannot_create_new_categories_through_import(): void
    {
        Sanctum::actingAs($this->memberOf($this->workspace, WorkspaceRole::Contributor));

        $this->post($this->url('imports/preview'), ['file' => $this->xlsx([
            ['2026-11-03', 'expense', 'Brand new', '10', '', '', '', '', ''],
        ])], ['Accept' => 'application/json'])
            ->assertJsonPath('data.rows.0.errors', ['Category "Brand new" does not exist and your role cannot create categories.']);
    }

    public function test_viewers_cannot_import_and_non_members_cannot_see_the_endpoints(): void
    {
        Sanctum::actingAs($this->memberOf($this->workspace, WorkspaceRole::Viewer));
        $this->post($this->url('imports'), ['file' => $this->xlsx([])], ['Accept' => 'application/json'])->assertForbidden();

        $other = Workspace::factory()->create();
        $this->getJson("/api/v1/workspaces/{$other->id}/export?format=csv&from=2026-11-01&to=2026-11-30")->assertNotFound();
    }

    public function test_uploads_must_be_spreadsheets(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->post($this->url('imports/preview'), ['file' => UploadedFile::fake()->create('photo.png', 10, 'image/png')], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors(['file' => 'Upload an Excel (.xlsx, .xls) or CSV file.']);
    }

    public function test_export_matches_the_dashboard_and_can_be_imported_again(): void
    {
        $groceries = Category::where('name', 'Groceries')->sole();
        Transaction::factory()->for($this->workspace)->create([
            'account_id' => $this->bank->id, 'category_id' => $groceries->id, 'type' => 'expense',
            'amount' => '300.00', 'return_amount' => '450.00', 'transaction_date' => '2026-11-03', 'merchant' => 'Wholesaler',
        ]);
        Transaction::factory()->for($this->workspace)->create([
            'account_id' => $this->bank->id, 'type' => 'income', 'amount' => '1000.00', 'transaction_date' => '2026-11-04',
        ]);
        Transaction::factory()->for($this->workspace)->create([
            'account_id' => $this->bank->id, 'type' => 'income', 'amount' => '5.00', 'transaction_date' => '2026-10-04',
        ]);
        Sanctum::actingAs($this->workspace->owner);

        $response = $this->get($this->url('export').'?format=xlsx&from=2026-11-01&to=2026-11-30')->assertOk();
        $response->assertDownload(str($this->workspace->name)->slug().'-2026-11-01-to-2026-11-30.xlsx');

        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $book->getSheetByName('Transactions');
        $this->assertSame(3, $sheet->getHighestDataRow());
        $this->assertEquals(['2026-11-03', 'expense', 'Groceries', 300.0, 450.0, 'Wholesaler'], array_slice($sheet->rangeToArray('A2:F2', null, false, false)[0], 0, 6));
        $this->assertEquals(150.0, $sheet->getCell('J2')->getValue());

        $summary = $book->getSheetByName('Summary');
        $this->assertSame('EUR', $summary->getCell('B3')->getValue());
        $this->assertSame('2026-11-01 to 2026-11-30', $summary->getCell('B4')->getValue());
        $this->assertEquals(1150.0, $summary->getCell('B10')->getValue()); // 1000 + 450 - 300

        $this->getJson($this->url('dashboard').'?month=2026-11')->assertJsonPath('data.totals.net', '1150.00');

        $preview = $this->post($this->url('imports/preview'), [
            'file' => new UploadedFile($response->baseResponse->getFile()->getPathname(), 'export.xlsx', null, null, true),
        ], ['Accept' => 'application/json']);
        $preview->assertJsonPath('data.summary.duplicates', 2);
    }

    public function test_export_requires_a_valid_period_and_format(): void
    {
        Sanctum::actingAs($this->workspace->owner);

        $this->getJson($this->url('export').'?format=pdf&from=2026-11-30&to=2026-11-01')
            ->assertJsonValidationErrors(['format', 'to']);
    }

    /**
     * @param  list<list<string|int|float>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Date', 'Type', 'Category', 'Amount', 'Return', 'Merchant', 'Note', 'Account', 'To account'],
            ...$rows,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'transactions.xlsx', null, null, true);
    }

    private function url(string $path): string
    {
        return "/api/v1/workspaces/{$this->workspace->id}/{$path}";
    }
}
