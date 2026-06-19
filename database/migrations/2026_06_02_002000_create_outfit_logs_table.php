<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outfit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stylist_history_id')->nullable()->constrained('stylist_history')->nullOnDelete();
            $table->string('name');
            $table->dateTime('logged_at');
            $table->string('occasion')->nullable();
            $table->string('weather')->nullable();
            $table->string('source')->default('ai_stylist');
            $table->json('selected_items');
            $table->json('item_ids')->nullable();
            $table->unsignedSmallInteger('item_count')->default(0);
            $table->json('context_json')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'logged_at']);
            $table->index(['stylist_history_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outfit_logs');
    }
};
