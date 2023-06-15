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
        if (!Schema::hasTable('account_properties')) {
            Schema::create('account_properties', function (Blueprint $table) {
                $table->bigInteger('aid')->unsigned();
                $table->char('propname', 100);
                $table->longText('propdata');
                $table->enum('proptype', ['STRING', 'INTEGER', 'FLOAT', 'OBJECT']);

                $table->primary(['aid', 'propname']);
                // $table->index(['aid']); -- This would be covered by the primary index of 'aid', 'propname'
                $table->index(['propname']);
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
