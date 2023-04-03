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
        if (!Schema::hasTable('patreon_claims')) {
            Schema::create('patreon_claims', function (Blueprint $table) {
                $table->integer('campaign_id');
                $table->integer('patron_id');
                $table->integer('claimed_cents');

                $table->comment("Patreon Member's Contributions to a campaign.");

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
