<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletInboundsTable extends Migration
{
    /**
     * Schema table name to migrate
     * @var string
     */
    public $tableName = 'wallet_inbounds';

    /**
     * Run the migrations.
     * @table wallet_locks
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id')->index();
            $table->string('reference', 100)->index(); 
            $table->string('currency', 10);
            $table->string('label', 50);
            $table->string('identifier', 45);
            $table->string('service_name', 50);
            $table->string('service_id', 50)->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->index();
            $table->json('data')->nullable();
            $table->string('driver', 45);
            $table->timestamps();


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
