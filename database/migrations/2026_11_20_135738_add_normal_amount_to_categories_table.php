<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('normal_amount', 19, 4)->nullable()->after('color');
            $table->unsignedSmallInteger('normal_tolerance')->default(20)->after('normal_amount');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['normal_amount', 'normal_tolerance']);
        });
    }
};
