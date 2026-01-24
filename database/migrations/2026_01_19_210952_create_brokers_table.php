<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->enum('type', ['company', 'individual'])
                ->default('company');
            $table->string('company_name')->nullable();
            $table->string('address')->nullable();
            $table->string('license_number')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('id_type', ['ID', 'Passport'])->nullable();
            $table->foreignId('nationality')
                ->nullable()
                ->constrained('countries')
                ->nullOnDelete();
            $table->char('otp', 6)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brokers');
    }
};
