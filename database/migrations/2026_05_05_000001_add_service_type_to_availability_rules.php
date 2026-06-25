<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->string('service_type', 10)->default('chat')->after('counselor_id');
        });

        // Update the index
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->index(['counselor_id', 'service_type', 'day_of_week', 'is_active'], 'avail_rules_composite');
        });
    }

    public function down(): void
    {
        Schema::table('availability_rules', function (Blueprint $table) {
            $table->dropIndex('avail_rules_composite');
            $table->dropColumn('service_type');
        });
    }
};
