<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A spreadsheet import; the checksum prevents importing the same file twice by accident.
 */
#[Fillable(['user_id', 'filename', 'checksum', 'rows_imported', 'rows_skipped'])]
class Import extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
