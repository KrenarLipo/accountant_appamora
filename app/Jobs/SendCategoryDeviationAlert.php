<?php

namespace App\Jobs;

use App\Contracts\ExplainsAlerts;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\CategoryDeviationDetected;
use App\Services\CategoryNormMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Builds the alert (with an optional AI explanation) and notifies the workspace's finance members.
 * Runs after the HTTP response is sent, so the AI call never slows down saving a transaction.
 */
class SendCategoryDeviationAlert
{
    use Dispatchable;

    public function __construct(
        public int $workspaceId,
        public int $categoryId,
        public string $period,
        public string $direction,
    ) {}

    public function handle(CategoryNormMonitor $monitor, ExplainsAlerts $explainer): void
    {
        $workspace = Workspace::find($this->workspaceId);
        $category = Category::find($this->categoryId);

        if (! $workspace || ! $category) {
            return;
        }

        $month = CarbonImmutable::createFromFormat('Y-m-d', $this->period.'-01', $workspace->timezone);
        $facts = $monitor->facts($workspace, $category, $month, $this->direction);
        $explanation = $explainer->explain($workspace, $facts);

        $notification = new CategoryDeviationDetected($workspace, $facts, $explanation);

        $monitor->recipients($workspace)->each(function (User $user) use ($notification): void {
            try {
                $user->notify($notification);
            } catch (TransportExceptionInterface $exception) {
                report($exception);
            }
        });
    }
}
