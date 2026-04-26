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
        Schema::create('ai_embeddings', function (Blueprint $table) {
            $table->id();

            // 所屬使用者
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 對應衣物；text search query 可以沒有 clothing_id
            $table->foreignId('clothing_id')
                ->nullable()
                ->constrained('clothes')
                ->nullOnDelete();

            // image / text
            $table->string('embedding_type');

            // clothing / search_query / outfit / chat
            $table->string('source_type')->nullable();

            // text embedding 的原始文字，例如「紅色約會洋裝」
            $table->text('source_text')->nullable();

            // 模型資訊
            $table->string('model')->nullable();
            $table->unsignedInteger('vector_dimension')->nullable();

            // SQLite 中 json 會以文字形式儲存，Laravel Model 可用 casts 轉 array
            $table->json('embedding')->nullable();
            $table->json('embedding_preview')->nullable();

            // 向量資料庫資訊
            $table->string('vector_provider')->nullable(); // qdrant / sqlite_fallback / mock
            $table->string('vector_collection')->nullable();
            $table->string('vector_point_id')->nullable();
            $table->boolean('vector_stored')->default(false);

            // AI 狀態
            $table->string('status')->default('pending'); // pending / success / degraded / failed
            $table->string('mode')->nullable(); // model / mock / fallback
            $table->string('degraded_reason')->nullable();

            // 原始回傳與錯誤資訊
            $table->json('raw_result')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // 常用查詢索引
            $table->index(['user_id', 'embedding_type']);
            $table->index(['clothing_id', 'embedding_type']);
            $table->index(['vector_provider', 'vector_collection']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_embeddings');
    }
};