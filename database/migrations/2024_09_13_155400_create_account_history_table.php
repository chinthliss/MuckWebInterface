<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('account_history')) {
            Schema::create('account_history', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->bigInteger('aid')->unsigned();
                $table->string('game', 255);
                $table->enum('msgtype', ['MAKO', 'SYSTEM']);
                $table->timestamp('when')->nullable()->useCurrent();
                $table->string('message', 255);
                $table->decimal('balance', 10, 2)->default(0);

                $table->index(['aid', 'when']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        throw new Error("Can not reverse this migration. Use 'artisan migrate:fresh --seed' for testing.");
    }
};
