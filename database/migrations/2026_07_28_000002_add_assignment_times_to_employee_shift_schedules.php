<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_shift_schedules', 'assignment_start_time')) {
                $table->time('assignment_start_time')->nullable()->after('assignment_location');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'assignment_end_time')) {
                $table->time('assignment_end_time')->nullable()->after('assignment_start_time');
            }
        });
    }

    public function down()
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('employee_shift_schedules', 'assignment_start_time')) {
                $table->dropColumn('assignment_start_time');
            }
            if (Schema::hasColumn('employee_shift_schedules', 'assignment_end_time')) {
                $table->dropColumn('assignment_end_time');
            }
        });
    }
};
