<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_remarks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label');
            $table->string('color', 20)->default('#6366f1');
            $table->boolean('counts_as_present')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('class_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attendance_period_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_remark_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('remarks')->nullable();
            $table->time('went_out_at')->nullable();
            $table->time('returned_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'class_period_id', 'date']);
            $table->index(['date', 'section_id']);
        });

        Schema::create('attendance_period_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_period_log_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 20);
            $table->text('remarks')->nullable();
            $table->timestamp('recorded_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('attendance_period_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_period_events');
        Schema::dropIfExists('attendance_period_logs');
        Schema::dropIfExists('class_periods');
        Schema::dropIfExists('attendance_remarks');
    }
};
