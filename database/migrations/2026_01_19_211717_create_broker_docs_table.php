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
        Schema::create('broker_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_user')
                ->constrained('brokers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('doc_type');
            $table->string('file_path');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broker_docs');
    }
};
