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
        if (!Schema::hasTable('patreon_members')) {
            Schema::create('patreon_members', function (Blueprint $table) {
                $table->integer('campaign_id')->unsigned();
                $table->integer('patron_id')->unsigned()->index();
                $table->integer('currently_entitled_amount_cents')->unsigned()->nullable();
                $table->boolean('is_follower')->nullable();
                $table->enum('last_charge_status', ['Paid', 'Declined', 'Deleted', 'Pending',
                    'Refunded', 'Fraud', 'Other'])->nullable();
                $table->dateTime('last_charge_date')->nullable();
                $table->integer('lifetime_support_cents')->unsigned()->nullable();
                $table->enum('patron_status',
                    ['active_patron', 'declined_patron', 'former_patron'])->nullable();
                $table->dateTime('pledge_relationship_start')->nullable();
                $table->string('url')->nullable();
                $table->string('thumb_url')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->primary(['campaign_id', 'patron_id']);

                $table->comment("Patreon User's membership to a given campaign");
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
