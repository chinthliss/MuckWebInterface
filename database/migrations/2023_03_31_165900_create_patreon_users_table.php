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
        if (!Schema::hasTable('patreon_users')) {
            Schema::create('patreon_users', function (Blueprint $table) {
                $table->integer('patron_id')->unsigned()->primary();
                $table->string('email')->index()->nullable();
                $table->string('full_name', 120)->nullable();
                $table->string('vanity', 100)->nullable();
                $table->boolean('hide_pledges')->nullable();
                $table->string('url')->nullable();
                $table->string('thumb_url')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->comment('Users of Patreon.');
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
