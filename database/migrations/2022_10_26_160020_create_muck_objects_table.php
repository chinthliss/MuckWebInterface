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
        if (!Schema::hasTable('muck_objects')) {
            Schema::create('muck_objects', function (Blueprint $table) {
                $table->id('id');
                $table->tinyInteger('game_code');
                $table->integer('dbref');
                $table->timestamp('created_at')->useCurrent(); // We don't want current, but not setting a default causes the DB to assume things
                $table->enum('type', ['player', 'zombie', 'room', 'thing']);
                $table->string('name', 255);
                $table->timestamp('deleted_at')->nullable()
                    ->comment('Only tracked for player objects, everything else is just deleted from this table.');

                $table->index(['game_code', 'dbref', 'created_at']);
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
