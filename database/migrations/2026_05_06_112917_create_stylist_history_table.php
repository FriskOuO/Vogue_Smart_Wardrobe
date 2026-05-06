<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stylist_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('occasion')->nullable();
            $table->string('weather')->nullable();
            $table->string('style_preference')->nullable();

            $table->json('selected_items')->nullable();
            $table->json('recommendation_json')->nullable();

            $table->string('status')->default('degraded');
            $table->string('mode')->default('rule_based');
            $table->boolean('is_accepted')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stylist_history');
    }
};