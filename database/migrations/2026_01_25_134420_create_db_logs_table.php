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
        Schema::create('db_logs', function (Blueprint $table) {
            $table->id();

            $table->string('level', 20); // info, warning, error, critical
            $table->string('category')->nullable(); // auth, booking, payment, api...
            $table->string('action')->nullable(); // create_booking, change_status...

            $table->text('message');

            $table->json('context')->nullable(); // payload, IDs, old/new values
            $table->json('meta')->nullable();    // ip, user_agent, route...

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->string('route')->nullable();
            $table->string('method', 10)->nullable();

            $table->timestamps();

            $table->index(['level', 'category']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_logs');
    }
};
