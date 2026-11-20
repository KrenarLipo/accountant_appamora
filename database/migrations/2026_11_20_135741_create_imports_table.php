<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->char('checksum', 64);
            $table->unsignedInteger('rows_imported');
            $table->unsignedInteger('rows_skipped');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workspace_id', 'checksum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
