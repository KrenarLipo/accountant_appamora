<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->boolean('ai_insights_enabled')->default(false)->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('ai_insights_enabled');
        });
    }
};
