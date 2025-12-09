#!/bin/bash

# Task migration
cat > Modules/Task/Data/Migrations/$(date +%Y_%m_%d_%H%M%S)_create_tasks_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->enum('status', ['backlog', 'todo', 'doing', 'done'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('estimated_time')->nullable()->comment('in minutes');
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
EOF

echo "✅ Migration tasks créée!"

# Task time logs migration
cat > Modules/Task/Data/Migrations/$(date +%Y_%m_%d_%H%M%S)_create_task_time_logs_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_time_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable()->comment('en minutes');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_time_logs');
    }
};
EOF

echo "✅ Migration task_time_logs créée!"

# Task files migration
cat > Modules/Task/Data/Migrations/$(date +%Y_%m_%d_%H%M%S)_create_task_files_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->string('file_url');
            $table->string('file_name');
            $table->integer('file_size')->comment('in bytes');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_files');
    }
};
EOF

echo "✅ Migration task_files créée!"

# Comments migration
cat > Modules/Task/Data/Migrations/$(date +%Y_%m_%d_%H%M%S)_create_comments_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
EOF

echo "✅ Migration task_comments créée!"

echo "🎉 Toutes les migrations ont été créées avec succès!"