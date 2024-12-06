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
        if (!Schema::hasTable('billing_profiles')) {
            Schema::create('billing_profiles', function (Blueprint $table) {
                $table->bigInteger('aid')->unsigned()->index();
                $table->bigInteger('profileid')->index();
                $table->bigInteger('defaultcard');
                $table->integer('spendinglimit');
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
