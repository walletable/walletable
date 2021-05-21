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
            $table->string('tag')->default('other')->index();
            $table->enum('type', ['credit', 'debit'])->index();
            $table->enum('action', ['transfer', 'hold', 'inbound', 'outbound', 'other'])->index();
            $table->unsignedBigInteger('amount');
            $table->nullableMolphs('method');
            $table->enum('status', ['approved', 'successful', 'unsuccessful', 'pending'])->index();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();


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
