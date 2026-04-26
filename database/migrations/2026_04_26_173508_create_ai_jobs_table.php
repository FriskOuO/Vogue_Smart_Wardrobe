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
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();

            // 所屬使用者
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 可選：若任務和某件衣物有關，例如 image_embedding / try_on
            $table->foreignId('clothing_id')
                ->nullable()
                ->constrained('clothes')
                ->nullOnDelete();

            // 任務類型：
            // clothing_attributes / image_embedding / text_embedding / similar_search
            // pose_analysis / stylist_recommendation / try_on / digital_twin
            // runway_video / trend_report / chat_response
            $table->string('job_type');

            // 任務狀態：
            // pending / processing / success / degraded / failed / pending_retry / cancelled
            $table->string('status')->default('pending');

            // 執行模式：model / mock / fallback
            $table->string('mode')->nullable();

            // 對應 API request_id
            $table->string('request_id')->nullable();

            // 任務輸入與輸出資料
            $table->json('input_json')->nullable();
            $table->json('result_json')->nullable();

            // 降級與錯誤資訊
            $table->string('degraded_reason')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            // 重試與時間追蹤
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // 常用查詢索引
            $table->index(['user_id', 'job_type']);
            $table->index(['status']);
            $table->index(['request_id']);
            $table->index(['clothing_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};