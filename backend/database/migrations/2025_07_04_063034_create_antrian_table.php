<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('antrian', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_antrian')->unique(); // R001, W001, etc
            $table->enum('jenis_antrian', ['reservasi', 'walk-in']);
            $table->unsignedBigInteger('antrian_status_id')->default(1); // default: waiting
            $table->timestamp('tanggal_waktu')->useCurrent();
            $table->timestamps();
            
            $table->foreign('antrian_status_id')->references('id')->on('status_antrian');
            $table->index(['jenis_antrian', 'tanggal_waktu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antrian');
    }
};