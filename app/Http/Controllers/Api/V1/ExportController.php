<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\Spreadsheets\TransactionSpreadsheetWriter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function show(Request $request, Workspace $workspace, TransactionSpreadsheetWriter $writer): BinaryFileResponse
    {
        Gate::authorize('view', $workspace);

        $validated = $request->validate([
            'format' => ['required', Rule::in(['xlsx', 'csv'])],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
        ]);

        $from = CarbonImmutable::createFromFormat('Y-m-d', $validated['from'], $workspace->timezone);
        $to = CarbonImmutable::createFromFormat('Y-m-d', $validated['to'], $workspace->timezone);

        $path = $writer->export($workspace, $from, $to, $validated, $validated['format']);
        $filename = sprintf('%s-%s-to-%s.%s', Str::slug($workspace->name), $from->toDateString(), $to->toDateString(), $validated['format']);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }
}
