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
        Schema::create('clothes', function (Blueprint $table) {
            $table->id();

            // 所屬使用者
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 基本衣物資料
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->text('notes')->nullable();

            // AI 屬性辨識結果
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('color')->nullable();
            $table->json('secondary_colors')->nullable();
            $table->json('season')->nullable();
            $table->json('occasion')->nullable();
            $table->json('usage')->nullable();
            $table->json('style_tags')->nullable();
            $table->string('material_guess')->nullable();
            $table->string('pattern')->nullable();

            // 使用者可手動補充資訊
            $table->string('brand')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('size')->nullable();

            // 穿著紀錄
            $table->unsignedInteger('wear_count')->default(0);
            $table->timestamp('last_worn_at')->nullable();

            // AI 狀態與原始結果
            $table->string('ai_status')->default('pending'); // pending / success / degraded / failed
            $table->string('ai_mode')->nullable(); // model / mock / fallback
            $table->decimal('ai_confidence', 5, 4)->nullable();
            $table->json('ai_raw_result')->nullable();
            $table->string('ai_error_code')->nullable();
            $table->text('ai_error_message')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 常用查詢索引
            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'color']);
            $table->index(['user_id', 'ai_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clothes');
    }
};