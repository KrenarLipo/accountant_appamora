<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->string('direction', 10);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['category_id', 'period', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_alerts');
    }
};
