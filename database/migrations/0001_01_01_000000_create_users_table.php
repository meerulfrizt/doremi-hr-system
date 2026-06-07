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
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        
        // --- TAMBAHAN UNTUK FYP ---
        $table->string('role')->default('staff'); // 'admin', 'supervisor', 'staff'
        $table->string('department')->nullable(); // 'Audio', 'Lighting', 'Visual' (PENTING untuk Supervisor)
        $table->string('position')->nullable();   // 'Senior Tech', 'Crew'
        $table->string('phone')->nullable();
        $table->string('photo_path')->nullable(); 
        $table->date('join_date')->nullable();
        
        // Balance Cuti
        $table->integer('annual_leave_balance')->default(14);
        $table->decimal('replacement_hours_balance', 8, 2)->default(0);
        
        $table->rememberToken();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
