<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    // 1. Tambah Baki Cuti dekat table USERS (KITA UNCOMMENT BALIK)
    Schema::table('users', function (Blueprint $table) {
        // Kita guna 'if (!Schema::hasColumn...)' untuk selamat, tak kira run fresh atau biasa
        if (!Schema::hasColumn('users', 'annual_leave_balance')) {
            $table->integer('annual_leave_balance')->default(14);
        }
        if (!Schema::hasColumn('users', 'medical_leave_balance')) {
            $table->integer('medical_leave_balance')->default(14);
        }
    });

    // 2. Tambah Details dekat table LEAVE_REQUESTS
    Schema::table('leave_requests', function (Blueprint $table) {
        if (!Schema::hasColumn('leave_requests', 'total_days')) {
            $table->integer('total_days')->default(1)->after('end_date'); 
        }
        if (!Schema::hasColumn('leave_requests', 'admin_remark')) {
            $table->text('admin_remark')->nullable()->after('status'); 
        }
    });
}

    public function down()
    {
        // Ini untuk reverse balik kalau salah
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['annual_leave_balance', 'medical_leave_balance']);
        });
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['total_days', 'admin_remark']);
        });
    }
};
