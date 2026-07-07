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
    Schema::create('flexible_hours_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        $table->date('date');
        $table->time('start_time')->nullable();
        $table->time('end_time')->nullable();
        
        // INI YANG HILANG TADI:
        $table->double('total_hours')->default(0); 
        
        $table->text('reason')->nullable();
        $table->string('status')->default('Pending');
        $table->text('admin_remark')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flexible_hours_requests');
    }
};
