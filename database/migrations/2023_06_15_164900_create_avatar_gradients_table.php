<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //The existing server already has this table so need to check if it exists or not
        if (!Schema::hasTable('avatar_gradients')) {
            Schema::create('avatar_gradients', function (Blueprint $table) {
                $table->string('name', 40)->primary();
                $table->string('description', 200);
                $table->dateTime('created_at')->useCurrent();
                $table->bigInteger('owner_aid')->unsigned()->nullable()->index();
                $table->boolean('free')->nullable();
                $table->json('steps_json');
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
        throw new Error("Can not reverse this migration. Use 'migrate:fresh --seed' for testing.");
    }
};
