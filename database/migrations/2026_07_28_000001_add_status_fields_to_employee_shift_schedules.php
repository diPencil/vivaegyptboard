<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_shift_schedules', 'status_type')) {
                $table->string('status_type')->nullable()->after('employee_shift_id')->comment('stable status codes for roster exceptions');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'leave_id')) {
                $table->unsignedBigInteger('leave_id')->nullable()->after('status_type');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'permitted_arrival_time')) {
                $table->time('permitted_arrival_time')->nullable()->after('leave_id');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'permitted_exit_time')) {
                $table->time('permitted_exit_time')->nullable()->after('permitted_arrival_time');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'half_day_period')) {
                $table->string('half_day_period')->nullable()->after('permitted_exit_time');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'reason')) {
                $table->text('reason')->nullable()->after('half_day_period');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('reason');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'attachment')) {
                $table->string('attachment')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('employee_shift_schedules', 'assignment_location')) {
                $table->string('assignment_location')->nullable()->after('attachment');
            }

            // company_id already exists in earlier migrations, do not add it here if present
            if (!Schema::hasColumn('employee_shift_schedules', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('assignment_location');
            }

            // skipping adding foreign keys here to avoid FK name/engine conflicts in existing schema
            // company_id foreign key assumed to exist from previous migrations; skip adding here
        });
    }

    public function down()
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            $table->dropForeign(['leave_id']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['company_id']);

            $table->dropColumn([
                'status_type', 'leave_id', 'permitted_arrival_time', 'permitted_exit_time',
                'half_day_period', 'reason', 'approved_by', 'attachment', 'assignment_location', 'company_id'
            ]);
        });
    }
};
