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
        Schema::table('global_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('global_settings', 'login_logo')) {
                $table->string('login_logo')->nullable();
            }
            if (!Schema::hasColumn('global_settings', 'login_ui')) {
                $table->string('login_ui')->default('both'); // both, logo, name
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'login_logo')) {
                $table->string('login_logo')->nullable();
            }
            if (!Schema::hasColumn('companies', 'login_ui')) {
                $table->string('login_ui')->default('both'); // both, logo, name
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn(['login_logo', 'login_ui']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['login_logo', 'login_ui']);
        });
    }
};
