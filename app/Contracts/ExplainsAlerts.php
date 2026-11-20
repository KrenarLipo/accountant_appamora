<?php

namespace App\Contracts;

use App\Models\Workspace;

interface ExplainsAlerts
{
    /**
     * A short plain-language explanation of an alert, or null when none is available.
     *
     * @param  array<string, mixed>  $facts  Figures already calculated by the application.
     */
    public function explain(Workspace $workspace, array $facts): ?string;

    public function isAvailable(): bool;
}
