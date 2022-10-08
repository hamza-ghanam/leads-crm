<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
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
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->string('invoice')->nullable();
            $table->string('passport')->nullable();
            $table->string('res_form')->nullable();
            $table->string('job_title')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade');
            $table->foreignId('status_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('source_id')
                ->constrained()
                ->onUpdate('cascade');
            $table->foreignId('assigner_id')
                ->default(null)
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade');
            $table->string('method')->nullable();
            $table->string('preferred_time')->nullable();
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('tickets');
    }
}
