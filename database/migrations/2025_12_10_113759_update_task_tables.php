<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Update task_comments table
        Schema::table('task_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('content');
            $table->boolean('edited')->default(false)->after('parent_id');
            $table->json('edit_history')->nullable()->after('edited');

            $table->foreign('parent_id')->references('id')->on('task_comments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'edited', 'edit_history']);
        });


    }
};