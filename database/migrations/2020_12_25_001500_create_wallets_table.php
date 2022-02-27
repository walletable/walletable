<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'wallets';

    /**
     * Run the migrations.
     * @table wallets
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->string('walletable_id', 100);
            $table->string('walletable_type', 45);
            $table->string('label', 45);
            $table->string('tag', 45)->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10);
            $table->enum('status', ['active', 'blocked'])->default('active')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['walletable_id', 'walletable_type']);
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
