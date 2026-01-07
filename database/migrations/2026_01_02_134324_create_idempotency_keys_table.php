<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->unsignedSmallInteger('user_id')->nullable();
            $table->string('method', 10);
            $table->string('uri', 512);
            $table->string('request_hash', 64);
            $table->longText('response');
            $table->unsignedSmallInteger('status_code');
            $table->timestamps();
            $table->unique(['key', 'method', 'uri']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
