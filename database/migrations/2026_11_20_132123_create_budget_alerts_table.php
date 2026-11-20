<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->unsignedSmallInteger('threshold');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['budget_id', 'period', 'threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_alerts');
    }
};
