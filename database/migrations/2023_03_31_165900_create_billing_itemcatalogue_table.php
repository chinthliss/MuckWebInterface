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
        if (!Schema::hasTable('billing_itemcatalogue')) {
            Schema::create('billing_itemcatalogue', function (Blueprint $table) {
                $table->char('code', 16)->primary();
                $table->string('name', 60);
                $table->text('description');
                $table->decimal('amount_usd', 8,2);
                $table->boolean('supporter')->nullable()
                    ->comment("Whether this item awards supporter points");
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
