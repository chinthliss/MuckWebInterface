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
        if (!Schema::hasTable('avatar_items')) {
            Schema::create('avatar_items', function (Blueprint $table) {
                $table->string('id', 40)->primary();
                $table->string('name', 80);
                $table->enum('type', ['item', 'background']);
                $table->string('filename', 50);
                $table->string('requirement', 80)->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->bigInteger('owner_aid')->unsigned()->nullable()->index();
                $table->smallInteger('cost')->nullable();
                $table->integer('x')->nullable();
                $table->integer('y')->nullable();
                $table->integer('rotate')->nullable();
                $table->decimal('scale')->nullable();
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
