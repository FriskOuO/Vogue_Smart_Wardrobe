<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stylist_history', function (Blueprint $table) {
            $table->json('context_json')->nullable()->after('style_preference');
        });
    }

    public function down(): void
    {
        Schema::table('stylist_history', function (Blueprint $table) {
            $table->dropColumn('context_json');
        });
    }
};
