<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('pause_start')->nullable();
            $table->time('pause_end')->nullable();
            $table->integer('total_seconds')->default(0); // Temps total en secondes
            $table->integer('pause_seconds')->default(0); // Temps de pause en secondes
            $table->integer('net_seconds')->default(0); // Temps net en secondes
            $table->enum('status', ['pending', 'in_progress', 'paused', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['user_id', 'work_date']);
            $table->unique(['user_id', 'work_date']);
        });

        // Table pour le suivi des sessions de travail
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_time_id')->constrained()->onDelete('cascade');
            $table->timestamp('session_start');
            $table->timestamp('session_end')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->enum('type', ['work', 'pause']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
        Schema::dropIfExists('work_times');
    }
};
