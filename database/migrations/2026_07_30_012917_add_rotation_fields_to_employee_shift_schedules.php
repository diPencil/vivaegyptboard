<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            $table->unsignedInteger('replacement_user_id')->nullable();
            $table->unsignedBigInteger('replacement_shift_id')->nullable();
            $table->unsignedBigInteger('rotation_source_schedule_id')->nullable();

            $table->foreign('replacement_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('replacement_shift_id')->references('id')->on('employee_shifts')->nullOnDelete();
            $table->foreign('rotation_source_schedule_id')->references('id')->on('employee_shift_schedules')->nullOnDelete();

            $table->unique('rotation_source_schedule_id', 'rotation_source_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            $table->dropForeign(['replacement_user_id']);
            $table->dropForeign(['replacement_shift_id']);
            $table->dropForeign(['rotation_source_schedule_id']);
            
            $table->dropUnique('rotation_source_unique');
            
            $table->dropColumn(['replacement_user_id', 'replacement_shift_id', 'rotation_source_schedule_id']);
        });
    }
};
