<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchivedLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('archived_leads', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('adset_id')->nullable();
            $table->string('adset_name')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('campaign_name')->nullable();
            $table->string('form_id')->nullable();
            $table->string('form_name')->nullable();
            $table->string('is_organic')->nullable();
            $table->string('platform')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('invoice')->nullable();
            $table->string('passport')->nullable();
            $table->string('res_form')->nullable();
            $table->string('job_title')->nullable();
            $table->string('user')->nullable();
            $table->string('status')->nullable();
            $table->string('source')->nullable();
            $table->dateTime('created_time')->nullable();;
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('archived_leads');
    }
}
