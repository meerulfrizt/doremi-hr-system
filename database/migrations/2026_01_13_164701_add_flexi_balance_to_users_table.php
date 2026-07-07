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
            Schema::table('users', function (Blueprint $table) {
                // Simpan dalam format perpuluhan (contoh: 2.5 jam)
                $table->double('flexi_balance')->default(0)->after('medical_leave_balance');
            });
        }

        public function down()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('flexi_balance');
            });
        }
};
