<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We ensure 'type' is nullable so the seeder doesn't fail,
        // as the actual ticket name is stored in the 'name' column.
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};