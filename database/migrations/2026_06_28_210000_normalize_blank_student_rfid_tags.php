<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->where('rfid_tag', '')->update(['rfid_tag' => null]);
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }
};
