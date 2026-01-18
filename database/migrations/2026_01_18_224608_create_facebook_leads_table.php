<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facebook_leads', function (Blueprint $table) {
            $table->id();

            $table->string('leadgen_id')->unique();          // idempotency key
            $table->string('page_id')->nullable();
            $table->string('form_id')->nullable();
            $table->string('ad_id')->nullable();
            $table->timestamp('fb_created_time')->nullable();

            // Normalized core fields (اختياري)
            $table->string('full_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // All answers normalized as key=>value (dynamic)
            $table->json('answers')->nullable();

            // Raw payloads
            $table->json('webhook_payload')->nullable();
            $table->json('graph_payload')->nullable();

            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_leads');
    }
};
