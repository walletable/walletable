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
            $table->primaryUuid();
            $table->indexedUuidMorphs('walletable');
            $table->string('label', 45);
            $table->string('name', 45);
            $table->unsignedBigInteger('amount');
            $table->uuid('currency_id');
            $table->json('data')->nullable();
            $table->string('provider', 45);
            $table->enum('status', ['active', 'blocked'])->index();
            $table->timestamps();

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
