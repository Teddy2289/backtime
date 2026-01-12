<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->string('file_url');
            $table->string('file_name');
            $table->integer('file_size')->comment('in bytes');
            $table->string('mime_type')->nullable(); // Enlevez le ->after()
            $table->string('extension')->nullable(); // Enlevez le ->after()
            $table->unsignedBigInteger('uploaded_by');
            $table->text('description')->nullable(); // Enlevez le ->after()
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');

            // Ajoutez des index pour les performances
            $table->index(['task_id']);
            $table->index(['uploaded_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_files');
    }
};