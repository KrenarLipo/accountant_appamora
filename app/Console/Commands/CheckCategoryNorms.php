<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use App\Services\CategoryNormMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accountant:check-category-norms')]
#[Description('Alert when a category ended last month above or below its normal amount')]
class CheckCategoryNorms extends Command
{
    public function handle(CategoryNormMonitor $monitor): int
    {
        $created = 0;

        Workspace::query()->whereHas('categories', fn ($query) => $query->whereNotNull('normal_amount'))
            ->each(function (Workspace $workspace) use ($monitor, &$created): void {
                $lastMonth = CarbonImmutable::now($workspace->timezone)->startOfMonth()->subMonth();
                $created += $monitor->check($workspace, $lastMonth, runAfterResponse: false);
            });

        $this->info("Created {$created} category alerts.");

        return self::SUCCESS;
    }
}
