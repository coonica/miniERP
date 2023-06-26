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
        Schema::table('invoice_tasks', function (Blueprint $table) {
            $table->dropColumn('desc');
            $table->string('note')->after('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_tasks', function (Blueprint $table) {
            $table->dropColumn('note');
            $table->string('desc')->after('invoice_id');
        });
    }
};
