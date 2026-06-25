<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_day_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Senin ... 6=Minggu
            $table->timestamps();
            $table->unique(['counselor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_day_offs');
    }
};
