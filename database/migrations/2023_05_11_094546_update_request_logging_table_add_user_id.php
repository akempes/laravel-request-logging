<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestLoggingTableAddUserId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('request-logging.database-logging.table'), function (Blueprint $table) {
            $table->integer('user_id')->nullable()->after('ip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('request-logging.database-logging.table'), function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}
