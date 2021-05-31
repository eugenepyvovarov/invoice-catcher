<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPdfBodyPathToGmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gmails', function (Blueprint $table) {
            $table->string('pdf_body_path')->nullable()->after('html_body');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gmails', function (Blueprint $table) {
            $table->dropColumn('pdf_body_path');
        });
    }
}
