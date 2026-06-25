<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Senin ... 6=Minggu
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['counselor_id', 'day_of_week', 'is_active']);
        });

        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->date('exception_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', ['blocked', 'added'])->default('blocked');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['counselor_id', 'exception_date', 'start_time'], 'unique_exception');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_exceptions');
        Schema::dropIfExists('availability_rules');
    }
};
