<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketPathsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prev_user')
                ->default(null)
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('next_user')
                ->default(null)
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('prev_status')
                ->default(null)
                ->nullable()
                ->constrained('statuses')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('next_status')
                ->constrained('statuses')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('ticket_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->text('comment');

            $table->timestamp('notified_at')->nullable()->after('comment');

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
        Schema::dropIfExists('ticket_paths');
    }
}
