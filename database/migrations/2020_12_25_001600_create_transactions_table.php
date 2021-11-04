<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'transactions';

    /**
     * Run the migrations.
     * @table transactions
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->string('session', 100)->index();
            $table->enum('type', ['credit', 'debit'])->index();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance');
            $table->string('action', 45)->index();
            $table->string('method_id', 100);
            $table->string('method_type', 45);
            $table->string('status', 45)->index();
            $table->string('driver', 45)->index();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['method_id', 'method_type']);


            $table->foreign('wallet_id')
                ->references('id')->on('wallets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
