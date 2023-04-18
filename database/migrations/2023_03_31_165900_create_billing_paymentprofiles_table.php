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
        if (!Schema::hasTable('billing_paymentprofiles')) {
            Schema::create('billing_paymentprofiles', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->bigInteger('profileid');
                $table->bigInteger('paymentid');
                $table->char('firstname', 255);
                $table->char('lastname', 255);
                $table->char('cardtype',50);
                $table->char('maskedcardnum', 25)
                    ->comment("Existing format is XXXX1234");
                $table->char('expdate', 25)
                    ->comment("Existing format is YYYY-MM");
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
