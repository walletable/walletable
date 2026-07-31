<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public $tableName = 'transactions';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->string('currency', 10);
            $table->enum('status', ['pending', 'posted', 'voided'])->default('pending');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('idempotency_hash', 64)->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->string('narration', 200)->nullable();
            $table->string('reference_type', 45)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->unsignedBigInteger('reverses_id')->nullable();
            $table->json('meta')->nullable();
            $table->json('draft_postings')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->index(['currency', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('reverses_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
}
