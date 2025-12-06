<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFcmTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            // صاحب الجهاز (المستخدم)
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // FCM token
            $table->string('token')->unique();

            // نوع الجهاز: موبايل / ديسكتوب / ويب
            $table->enum('device_type', ['desktop', 'mobile', 'mobile-web'])
                ->default('desktop');

            // user agent (اختياري للتتبع)
            $table->text('user_agent')->nullable();

            // آخر استخدام للتوكن (مثلاً آخر مرة وصل إشعار)
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            // لو حابب تبحث سريعاً عن توكنات المستخدم
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
