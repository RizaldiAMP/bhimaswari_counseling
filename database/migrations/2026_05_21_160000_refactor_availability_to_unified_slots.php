<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the composite index that includes service_type
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropIndex('avail_rules_composite');
        });

        // 2. Remove service_type and end_time columns from availability_rules
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'end_time']);
        });

        // 3. Add a unique constraint: one slot per counselor per day per time
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->unique(['counselor_id', 'day_of_week', 'start_time'], 'unique_counselor_day_slot');
        });

        // 4. Remove end_time from availability_exceptions
        Schema::table('availability_exceptions', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }

    public function down(): void
    {
        // Reverse: add back end_time to availability_exceptions
        Schema::table('availability_exceptions', function (Blueprint $table) {
            $table->time('end_time')->nullable()->after('start_time');
        });

        // Reverse: drop the unique constraint
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropUnique('unique_counselor_day_slot');
        });

        // Reverse: add back service_type and end_time to availability_rules
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->string('service_type', 10)->default('chat')->after('counselor_id');
            $table->time('end_time')->after('start_time');
        });

        // Reverse: recreate composite index
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->index(['counselor_id', 'service_type', 'day_of_week', 'is_active'], 'avail_rules_composite');
        });
    }
};
