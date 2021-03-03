<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletTransactionsTable extends Migration
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
            $table->primaryUuid();
            $table->uuid('wallet_id');
            $table->enum('type', ['credit', 'debit']);
            $table->string('session', 100);
            $table->enum('action', ['wallet_transfer', 'bank_transfer', 'purchase']);
            $table->uuid('to_wallet')->nullable();
            $table->json('data')->nullable();
            $table->enum('status', ['approved', 'successful', 'unsuccessful', 'pending']);
            $table->string('driver', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index("session");

            $table->index("to_wallet");

            $table->index("status");

            $table->index("type");

            $table->index("action");

            $table->index("wallet_id");


            $table->foreign('wallet_id')
                ->references('id')->on('wallets');

            $table->foreign('to_wallet')
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
