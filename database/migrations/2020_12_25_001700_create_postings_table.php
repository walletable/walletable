<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostingsTable extends Migration
{
    public $tableName = 'postings';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->index();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->char('direction', 1);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10);
            $table->bigInteger('balance_after');
            $table->string('action', 45)->index();
            $table->string('method_id', 100)->nullable();
            $table->string('method_type', 45)->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('posted_at');

            $table->index(['method_id', 'method_type']);
            $table->index(['wallet_id', 'posted_at']);

            $table->foreign('transaction_id')->references('id')->on('transactions');
            $table->foreign('wallet_id')->references('id')->on('wallets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
}
