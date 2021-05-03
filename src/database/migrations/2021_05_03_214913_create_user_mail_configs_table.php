<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMailConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_mail_configs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->json('config')->nullable();
            $table->string('type');
            $table->string('status');
            $table->integer('initial_sync_days')->default(30);
            $table->uuid('state_uuid')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_mail_configs');
    }
}
