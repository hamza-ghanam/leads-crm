<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // عنوان الإشعار (للـ toast / الـ bell)
            $table->string('title');

            // نص الإشعار
            $table->text('body');

            // رابط داخلي بالنظام (اختياري)
            $table->string('url')->nullable();

            // نوع الإشعار (reminder, ticket_follow_up, status_changed, ...)
            $table->string('type', 50)->nullable();

            // أيقونة مخصصة (اختياري)
            $table->string('icon')->nullable();

            // هل تمّت قراءته من واجهة النظام (notification bell)
            $table->boolean('is_read')->default(false);

            // متى تم تسليمه للمتصفح (استلام FCM + إظهار toast مثلاً)
            $table->timestamp('delivered_at')->nullable();

            // متى ضغط المستخدم على الإشعار (click)
            $table->timestamp('clicked_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_read']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
