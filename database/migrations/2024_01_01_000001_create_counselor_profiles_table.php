<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('practitioner_type', ['psychologist', 'counselor']);
            $table->string('full_title');
            $table->string('sipp_number')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->json('specializations')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['is_visible', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counselor_profiles');
    }
};
