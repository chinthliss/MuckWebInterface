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
        if (!Schema::hasTable('log_communication')) {
            Schema::create('log_communication', function (Blueprint $table) {
                $table->bigIncrements('id')->autoIncrement();
                $table->tinyInteger('game')->unsigned();
                $table->dateTime('when_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->enum('type', ['IC','OOC','Page','Mail','Channel']);

                $table->bigInteger('from_aid')->nullable()->default(null);
                $table->integer('from_dbref');
                $table->string('from_name', 24);
                $table->timestamp('from_created')->default(null);

                $table->bigInteger('to_aid')->nullable()->default(null);
                $table->integer('to_dbref')->nullable()->default(null);
                $table->string('to_name', 255);
                $table->timestamp('to_created')->nullable()->default(null);

                $table->mediumText('content');

                $table->index(['game', 'type', 'from_dbref', 'when_at'], name: 'from_player');
                $table->index(['game', 'type', 'to_dbref', 'when_at'], name: 'to_player');
                $table->index(['game', 'type', 'to_name', 'when_at'], name: 'to_channel');
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
