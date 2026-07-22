<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN salutation ENUM('mr', 'mrs', 'miss', 'dr', 'sir', 'madam', 'eng') DEFAULT NULL");
        DB::statement("ALTER TABLE leads MODIFY COLUMN salutation ENUM('mr', 'mrs', 'miss', 'dr', 'sir', 'madam', 'eng') DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN salutation ENUM('mr', 'mrs', 'miss', 'dr', 'sir', 'madam') DEFAULT NULL");
        DB::statement("ALTER TABLE leads MODIFY COLUMN salutation ENUM('mr', 'mrs', 'miss', 'dr', 'sir', 'madam') DEFAULT NULL");
    }
};
