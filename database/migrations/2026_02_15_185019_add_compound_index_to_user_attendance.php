<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_attendance', function (Blueprint $table) {
            $table->index(['user_id', 'check_in', 'check_out'], 'ua_user_checkin_checkout_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_attendance', function (Blueprint $table) {
            $table->dropIndex('ua_user_checkin_checkout_idx');
        });
    }
};
