<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('transactions', 'confirmed')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->boolean('confirmed')->default(true);
            });
        }
        if (!Schema::hasColumn('transactions', 'confirmed_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dateTime('confirmed_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'confirmed')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('confirmed');
            });
        }
        if (Schema::hasColumn('transactions', 'confirmed_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('confirmed_at');
            });
        }
    }
};
