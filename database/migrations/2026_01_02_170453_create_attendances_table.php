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
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        $table->date('date');
        $table->dateTime('clock_in_time')->nullable();
        $table->dateTime('clock_out_time')->nullable();
        
        $table->string('photo')->nullable(); // Bukti gambar
        $table->string('location')->nullable(); // GPS
        $table->string('status')->default('Absent'); // Present, Late, Absent
        
        // --- FEATURE IRREGULAR DETECT ---
        // 0 = Normal, 1 = Ada Pattern Pelik (Keluar Warning)
        $table->boolean('irregular_flag')->default(false); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
