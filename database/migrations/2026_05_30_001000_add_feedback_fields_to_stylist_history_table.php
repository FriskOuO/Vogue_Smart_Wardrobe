<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stylist_history', function (Blueprint $table) {
            $table->string('feedback_status')->nullable()->after('is_accepted');
            $table->text('feedback_reason')->nullable()->after('feedback_status');
            $table->json('feedback_json')->nullable()->after('feedback_reason');
            $table->timestamp('feedback_submitted_at')->nullable()->after('feedback_json');

            $table->index(['feedback_status', 'feedback_submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stylist_history', function (Blueprint $table) {
            $table->dropIndex(['feedback_status', 'feedback_submitted_at']);
            $table->dropColumn([
                'feedback_status',
                'feedback_reason',
                'feedback_json',
                'feedback_submitted_at',
            ]);
        });
    }
};
