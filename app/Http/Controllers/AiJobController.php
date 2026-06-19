<?php

namespace App\Http\Controllers;

use App\Models\AiJob;
use App\Services\ExternalModelProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AiJobController extends Controller
{
    public function show(AiJob $job): JsonResponse
    {
        $this->ensureOwnedJob($job);

        return response()->json([
            'id' => $job->id,
            'job_type' => $job->job_type,
            'status' => $job->status,
            'mode' => $job->mode,
            'request_id' => $job->request_id,
            'retry_count' => $job->retry_count,
            'input' => $job->input_json,
            'result' => $job->result_json,
            'error_code' => $job->error_code,
            'error_message' => $job->error_message,
            'started_at' => optional($job->started_at)->toISOString(),
            'completed_at' => optional($job->completed_at)->toISOString(),
            'updated_at' => optional($job->updated_at)->toISOString(),
        ]);
    }

    public function retry(AiJob $job): RedirectResponse
    {
        $this->ensureOwnedJob($job);

        $result = $job->result_json ?? [];
        $result['retry'] = [
            'requested_at' => now()->toISOString(),
            'previous_status' => $job->status,
            'previous_error_code' => $job->error_code,
            'source_job_id' => $job->id,
        ];

        $job->forceFill([
            'status' => 'pending_retry',
            'retry_count' => (int) $job->retry_count + 1,
            'result_json' => $result,
            'error_code' => null,
            'error_message' => null,
            'completed_at' => null,
        ])->save();

        return redirect()
            ->to($this->redirectPath($job))
            ->with('status', '已將 AI 任務標記為重新查詢，請依照頁面流程再次確認結果。');
    }

    public function refreshTryOnStatus(
        Request $request,
        AiJob $job,
        ExternalModelProviderService $externalModelProvider,
    ): JsonResponse|RedirectResponse
    {
        $this->ensureOwnedJob($job);
        abort_unless($job->job_type === 'pose_analysis', 404);

        $result = $job->result_json ?? [];
        $attempt = data_get($result, 'tryon_provider_attempt', []);
        $providerTaskId = data_get($attempt, 'provider_task_id') ?: data_get($attempt, 'provider_job_id');

        if (! is_string($providerTaskId) || $providerTaskId === '') {
            return redirect()
                ->route('closet.tryon')
                ->with('status', '這筆試穿任務還沒有外部 provider task id，請重新建立試穿任務。');
        }

        $providerStatus = $externalModelProvider->pollTryOn($providerTaskId, $job->request_id);
        $status = (string) ($providerStatus['status'] ?? 'degraded');

        $result['tryon_provider_attempt'] = [
            ...$attempt,
            'latest_status' => $providerStatus,
            'last_checked_at' => now()->toISOString(),
        ];
        $result['tryon_provider_status'] = $providerStatus;

        if (is_string($providerStatus['output_url'] ?? null) && $providerStatus['output_url'] !== '') {
            $result['tryon_output_url'] = $providerStatus['output_url'];
        }

        $job->forceFill([
            'status' => $status,
            'mode' => $providerStatus['mode'] ?? $job->mode,
            'result_json' => $result,
            'error_code' => in_array($status, ['failed', 'degraded'], true) ? ($providerStatus['error_code'] ?? null) : null,
            'error_message' => in_array($status, ['failed', 'degraded'], true) ? ($providerStatus['error_message'] ?? null) : null,
            'completed_at' => in_array($status, ['success', 'failed', 'degraded'], true) ? now() : null,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $status,
                'output_url' => $result['tryon_output_url'] ?? null,
                'error_code' => $job->error_code,
                'error_message' => $job->error_message,
            ]);
        }

        $message = match ($status) {
            'success' => '試穿圖片已產生，結果已更新在任務紀錄中。',
            'processing' => '試穿任務仍在處理中，稍後可再查詢一次。',
            'failed' => '試穿任務失敗，已保留錯誤訊息並可回到姿態分析結果。',
            default => '已查詢試穿任務狀態，外部服務目前不可用或尚未完成。',
        };

        return redirect()
            ->route('closet.tryon')
            ->with('status', $message);
    }

    private function ensureOwnedJob(AiJob $job): void
    {
        abort_unless((int) $job->user_id === (int) auth()->id(), 404);
    }

    private function redirectPath(AiJob $job): string
    {
        return match ($job->job_type) {
            'pose_analysis' => route('closet.tryon'),
            'runway_video' => route('workspace.show', 'runway-video'),
            'digital_twin', 'digital_twin_style_analysis' => route('workspace.show', 'digital-twin'),
            default => route('closet.hub'),
        };
    }
}
