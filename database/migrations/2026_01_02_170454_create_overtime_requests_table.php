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
    Schema::create('overtime_requests', function (Blueprint $table) {
        $table->id();
        
        // Ini column yang hilang tadi:
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Link ke User
        $table->string('event_name');
        $table->date('date');
        $table->time('start_time');
        $table->time('end_time');
        $table->integer('duration_hours'); // Atau double kalau nak point
        $table->text('reason');
        $table->string('status')->default('Pending'); // Status default
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};
