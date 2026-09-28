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
        Schema::table('bathroom_permissions', function (Blueprint $table) {
            $table->string('bathroom')->nullable()->after('alumn_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bathroom_permissions', function (Blueprint $table) {
            $table->dropColumn('bathroom');
        });
    }
};
