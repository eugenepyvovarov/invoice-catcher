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
        Schema::create('gmails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('gmail_profile_id')->nullable(); // @todo
            $table->unsignedBigInteger('gmail_filter_id')->nullable();
            $table->string('mail_id')->nullable();
            $table->unsignedBigInteger('internal_date')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->json('labels')->nullable();
            $table->json('to')->nullable();
            $table->string('delivered_to')->nullable();
            $table->string('subject')->nullable();
            $table->longText('html_body')->nullable();
            $table->string('pdf_body_path')->nullable();
            $table->json('attachments')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gmails');
    }
}
;
