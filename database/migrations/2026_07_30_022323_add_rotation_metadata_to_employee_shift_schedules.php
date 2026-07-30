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
            $table->string('rotation_coverage_mode')->nullable();
            $table->unsignedBigInteger('rotation_previous_shift_id')->nullable();

            $table->foreign('rotation_previous_shift_id')->references('id')->on('employee_shifts')->nullOnDelete();
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
            $table->dropForeign(['rotation_previous_shift_id']);
            $table->dropColumn(['rotation_coverage_mode', 'rotation_previous_shift_id']);
        });
    }
};
