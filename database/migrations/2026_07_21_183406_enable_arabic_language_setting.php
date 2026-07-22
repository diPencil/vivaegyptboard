<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $arabicExists = DB::table('language_settings')->where('language_code', 'ar')->exists();

        if ($arabicExists) {
            DB::table('language_settings')
                ->where('language_code', 'ar')
                ->update(['status' => 'enabled']);
        } else {
            DB::table('language_settings')->insert([
                'language_code' => 'ar',
                'language_name' => 'Arabic',
                'status' => 'enabled',
                'flag_code' => 'sa',
                'is_rtl' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally perform no action here.
        // Reason:
        // - Arabic may have existed before the migration.
        // - Its prior status is not safely trackable without an extra history table.
        // - Rollback intentionally leaves the language enabled to avoid corruption.
        // - This migration is idempotent (safe to run multiple times without failure).
    }
};
