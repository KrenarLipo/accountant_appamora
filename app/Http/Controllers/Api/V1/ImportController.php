<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\BudgetTracker;
use App\Services\CategoryNormMonitor;
use App\Services\Spreadsheets\TransactionImporter;
use App\Services\Spreadsheets\TransactionSpreadsheetWriter;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function __construct(private TransactionImporter $importer) {}

    public function template(Workspace $workspace, TransactionSpreadsheetWriter $writer): BinaryFileResponse
    {
        Gate::authorize('view', $workspace);

        return response()
            ->download($writer->template($workspace), 'accountant-import-template.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Validates the file and shows what would be imported, without saving anything.
     */
    public function preview(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('recordTransactions', $workspace);

        $path = $this->validatedFile($request);
        $preview = $this->importer->preview($workspace, $path, $this->canCreateCategories($request, $workspace));
        $previous = $this->importer->alreadyImported($workspace, $path);

        return response()->json(['data' => [
            ...$preview,
            'already_imported_at' => $previous?->created_at?->toIso8601String(),
        ]]);
    }

    public function store(Request $request, Workspace $workspace, BudgetTracker $budgetTracker, CategoryNormMonitor $normMonitor): JsonResponse
    {
        Gate::authorize('recordTransactions', $workspace);

        $path = $this->validatedFile($request);

        if (! $request->boolean('allow_reimport') && $this->importer->alreadyImported($workspace, $path)) {
            return response()->json([
                'message' => 'This exact file was already imported. Tick "import again" if you really want to add it twice.',
            ], 422);
        }

        $result = $this->importer->import(
            $workspace,
            $request->user(),
            $path,
            $request->file('file')->getClientOriginalName(),
            $this->canCreateCategories($request, $workspace),
        );

        $currentMonth = CarbonImmutable::now($workspace->timezone)->format('Y-m');
        if (in_array($currentMonth, $result['months'], true)) {
            $budgetTracker->checkAlerts($workspace, CarbonImmutable::now($workspace->timezone));
            $normMonitor->check($workspace, CarbonImmutable::now($workspace->timezone));
        }

        return response()->json(['data' => $result], 201);
    }

    private function validatedFile(Request $request): string
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt'],
        ], [
            'file.mimes' => 'Upload an Excel (.xlsx, .xls) or CSV file.',
        ]);

        return $request->file('file')->getRealPath();
    }

    private function canCreateCategories(Request $request, Workspace $workspace): bool
    {
        return $request->user()->can('manageFinances', $workspace);
    }
}
