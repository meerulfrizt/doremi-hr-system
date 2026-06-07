<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Check dulu: Kalau column BELUM ADA, baru kita buat.
        if (!Schema::hasColumn('overtime_requests', 'admin_remarks')) {
            Schema::table('overtime_requests', function (Blueprint $table) {
                $table->text('admin_remarks')->nullable()->after('status');
            });
        }
    }

    public function down()
    {
        // Kalau nak rollback, check dulu kalau column tu wujud
        if (Schema::hasColumn('overtime_requests', 'admin_remarks')) {
            Schema::table('overtime_requests', function (Blueprint $table) {
                $table->dropColumn('admin_remarks');
            });
        }
    }
};