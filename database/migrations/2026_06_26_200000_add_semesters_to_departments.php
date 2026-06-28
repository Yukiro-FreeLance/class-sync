<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->json('semesters')->nullable()->after('is_active');
        });

        if (Schema::hasTable('departments')) {
            DB::table('departments')->whereNull('semesters')->update([
                'semesters' => json_encode(['first', 'second']),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('semesters');
        });
    }
};
