<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHoursColumnsToWorkTimesTable extends Migration
{
    public function up()
    {
        Schema::table('work_times', function (Blueprint $table) {
            $table->decimal('net_hours', 8, 2)->nullable()->after('net_seconds');
            $table->decimal('pause_hours', 8, 2)->nullable()->after('pause_seconds');
            $table->decimal('progress_percentage', 5, 2)->nullable()->after('net_hours');
            $table->integer('daily_target')->default(28800)->after('status'); // 8h en secondes
        });
    }

    public function down()
    {
        Schema::table('work_times', function (Blueprint $table) {
            $table->dropColumn(['net_hours', 'pause_hours', 'progress_percentage', 'daily_target']);
        });
    }
}