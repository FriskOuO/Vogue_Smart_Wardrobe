<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class ProductionReadinessService
{
    public function __construct(
        private readonly ModelProviderMatrixService $modelProviderMatrix,
    ) {
    }

    /**
     * @param array<int, string>|null $statusLines
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(?array $statusLines = null): array
    {
        $statusLines ??= $this->gitStatusLines();

        $checks = [
            $this->checkProductionEnvironment(),
            $this->checkAiProviderConfiguration(),
            $this->checkModelProviderMatrix(),
            $this->checkQueueStorageAndLogs(),
            $this->checkLegalPages(),
            $this->checkErrorAndStateUx(),
            $this->checkSecretAndModelSafety($statusLines),
        ];

        $failed = collect($checks)->where('status', 'fail')->count();
        $warnings = collect($checks)->where('status', 'warn')->count();

        return [
            'ok' => $failed === 0,
            'failed' => $failed,
            'warnings' => $warnings,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkProductionEnvironment(): array
    {
        $isProduction = (string) config('app.env') === 'production';
        $issues = [];

        if ($isProduction && config('app.debug')) {
            $issues[] = 'APP_DEBUG must be false in production.';
        }

        if ($isProduction && blank((string) config('app.key'))) {
            $issues[] = 'APP_KEY must be generated.';
        }

        $appUrl = (string) config('app.url');
        if ($isProduction && (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1'))) {
            $issues[] = 'APP_URL must point to the public production domain.';
        }

        if (! empty($issues)) {
            return [
                'name' => 'Production environment',
                'status' => 'fail',
                'message' => implode(' ', $issues),
                'required' => true,
            ];
        }

        return [
            'name' => 'Production environment',
            'status' => $isProduction ? 'pass' : 'warn',
            'message' => $isProduction
                ? 'Core production environment values are safe.'
                : 'Current APP_ENV is not production; rerun this gate with production values before launch.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiProviderConfiguration(): array
    {
        $isProduction = (string) config('app.env') === 'production';
        $mockMode = (bool) config('ai.mock_mode');
        $provider = (string) config('ai.text_generation_provider', '');
        $model = (string) config('ai.gemini_text_model', '');
        $apiKeyConfigured = filled((string) config('ai.gemini_api_key', ''));
        $internalToken = (string) config('ai.internal_token', '');
        $issues = [];

        if ($isProduction && $mockMode) {
            $issues[] = 'AI_MOCK_MODE must be false for production.';
        }

        if ($isProduction && $provider === 'gemini' && ! $apiKeyConfigured) {
            $issues[] = 'GEMINI_API_KEY must be configured for production Gemini text generation.';
        }

        if ($isProduction && ($internalToken === '' || $internalToken === 'change_this_internal_ai_token')) {
            $issues[] = 'AI_INTERNAL_TOKEN must be replaced before production.';
        }

        if ($model === '') {
            $issues[] = 'GEMINI_TEXT_MODEL cannot be empty.';
        }

        if (! empty($issues)) {
            return [
                'name' => 'AI provider configuration',
                'status' => 'fail',
                'message' => implode(' ', $issues),
                'required' => true,
            ];
        }

        return [
            'name' => 'AI provider configuration',
            'status' => ($isProduction || (! $mockMode && $apiKeyConfigured)) ? 'pass' : 'warn',
            'message' => $isProduction
                ? 'AI provider settings are production-ready; API key value is not printed.'
                : 'Local mode may still use fallback; production must use real provider credentials.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkModelProviderMatrix(): array
    {
        $summary = $this->modelProviderMatrix->summary();

        return [
            'name' => 'Model provider matrix',
            'status' => $summary['failed'] > 0 ? 'fail' : ($summary['warnings'] > 0 ? 'warn' : 'pass'),
            'message' => sprintf(
                'Model providers: %d failed / %d warnings across %d capabilities.',
                $summary['failed'],
                $summary['warnings'],
                count($summary['providers'])
            ),
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkQueueStorageAndLogs(): array
    {
        $isProduction = (string) config('app.env') === 'production';
        $queue = (string) config('queue.default');
        $issues = [];
        $warnings = [];

        if ($isProduction && in_array($queue, ['sync', 'null'], true)) {
            $issues[] = 'QUEUE_CONNECTION must not be sync/null in production.';
        } elseif (! $isProduction && in_array($queue, ['sync', 'null'], true)) {
            $warnings[] = 'Local queue is sync/null; production should use database, redis, or another worker-backed queue.';
        }

        foreach ([storage_path('logs'), storage_path('framework/cache'), storage_path('framework/sessions'), storage_path('framework/views')] as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $issues[] = 'Storage path must be writable: ' . $this->relativePath($path);
            }
        }

        if (! file_exists(public_path('storage'))) {
            $warnings[] = 'Public storage link is missing; run php artisan storage:link before handling user uploads in production.';
        }

        if (! empty($issues)) {
            return [
                'name' => 'Queue, storage, and logs',
                'status' => 'fail',
                'message' => implode(' ', $issues),
                'required' => true,
            ];
        }

        return [
            'name' => 'Queue, storage, and logs',
            'status' => empty($warnings) ? 'pass' : 'warn',
            'message' => empty($warnings)
                ? 'Queue setting, storage directories, and log paths are ready.'
                : implode(' ', $warnings),
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkLegalPages(): array
    {
        $routesReady = Route::has('legal.privacy')
            && Route::has('legal.terms')
            && Route::has('legal.acceptable-use');

        $viewsReady = view()->exists('legal.privacy')
            && view()->exists('legal.terms')
            && view()->exists('legal.acceptable-use');

        return [
            'name' => 'Privacy, terms, and use limits',
            'status' => ($routesReady && $viewsReady) ? 'pass' : 'fail',
            'message' => ($routesReady && $viewsReady)
                ? 'Public privacy, terms, and acceptable-use pages are registered.'
                : 'Privacy, terms, or acceptable-use routes/views are missing.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkErrorAndStateUx(): array
    {
        $requiredFiles = [
            resource_path('views/components/vogue-state.blade.php'),
            resource_path('views/errors/500.blade.php'),
            resource_path('views/errors/503.blade.php'),
        ];

        $missing = collect($requiredFiles)
            ->reject(fn (string $path) => file_exists($path))
            ->map(fn (string $path) => $this->relativePath($path))
            ->values()
            ->all();

        return [
            'name' => 'Error, loading, empty, and retry UX',
            'status' => empty($missing) ? 'pass' : 'fail',
            'message' => empty($missing)
                ? 'Reusable state component and production error pages are present.'
                : 'Missing production UX files: ' . implode(', ', $missing),
            'required' => true,
        ];
    }

    /**
     * @param array<int, string> $statusLines
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkSecretAndModelSafety(array $statusLines): array
    {
        $entries = collect($statusLines)
            ->filter(fn (string $line) => preg_match('/^[ MADRCU?!]{2}\s+.+$/', $line) === 1)
            ->map(fn (string $line) => $this->statusPath($line))
            ->values();

        $unsafe = $entries->filter(function (string $path) {
            return in_array($path, ['.env', '.env.backup', '.env.production', 'ai_service/.env'], true)
                || preg_match('/\.(safetensors|bin|pt|pth|onnx|gguf|ckpt)$/i', $path) === 1;
        })->values();

        return [
            'name' => 'Secret and model artifact safety',
            'status' => $unsafe->isEmpty() ? 'pass' : 'fail',
            'message' => $unsafe->isEmpty()
                ? 'No local .env, API key file, or large model artifact is listed by git status.'
                : 'Unsafe files are listed by git status: ' . $unsafe->implode(', '),
            'required' => true,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function gitStatusLines(): array
    {
        $repoPath = str_replace('\\', '/', base_path());

        $process = proc_open(
            'git -C ' . escapeshellarg($repoPath) . ' status --short --untracked-files=all',
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (! is_resource($process)) {
            return ['!! git status failed: unable to start git status'];
        }

        $output = stream_get_contents($pipes[1]) ?: '';
        $errorOutput = stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return collect([$output, $errorOutput])
            ->flatMap(fn (string $text) => preg_split('/\R/', $text) ?: [])
            ->map(fn (string $line) => rtrim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }

    private function statusPath(string $line): string
    {
        $path = trim(substr($line, 3));

        if (str_contains($path, ' -> ')) {
            return trim((string) str($path)->afterLast(' -> '));
        }

        return trim($path, '"');
    }

    private function relativePath(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $normalized = str_replace('\\', '/', $path);

        if (str_starts_with($normalized, $base)) {
            return ltrim(substr($normalized, strlen($base)), '/');
        }

        return $path;
    }
}
