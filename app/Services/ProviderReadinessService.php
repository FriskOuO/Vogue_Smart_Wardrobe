<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProviderReadinessService
{
    public function __construct(
        private readonly ModelProviderMatrixService $modelProviderMatrix,
    ) {
    }

    /**
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $checks = [
            $this->checkPath('AI service venv python', base_path('ai_service/.venv/Scripts/python.exe'), true),
            $this->checkPath('ML requirements', base_path('ai_service/requirements-ml.txt'), true),
            $this->checkPath('Qdrant launcher', base_path('start-qdrant.ps1'), true),
            $this->checkPath('CLIP model cache', base_path('ai_service/models/huggingface/hub/models--openai--clip-vit-base-patch32'), false),
            $this->checkPath('BLIP model cache', base_path('ai_service/models/huggingface/hub/models--Salesforce--blip-image-captioning-base'), false),
            $this->checkAiSearchRealModeEntry(),
            $this->checkStylistRealModeEntry(),
            $this->checkAiServiceHealth(),
            $this->checkQdrantPreflight(),
            $this->checkGeminiConfig(),
            $this->checkModelProviderMatrix(),
            $this->checkIgnoredLocalProviderArtifacts(),
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
    private function checkPath(string $name, string $path, bool $required): array
    {
        $exists = file_exists($path);

        return [
            'name' => $name,
            'status' => $exists ? 'pass' : ($required ? 'fail' : 'warn'),
            'message' => $exists
                ? 'Found: ' . $this->relativePath($path)
                : 'Missing: ' . $this->relativePath($path),
            'required' => $required,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiSearchRealModeEntry(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/search.blade.php'));
        $ready = str_contains($controller, 'provider_mode')
            && str_contains($controller, "'mock_mode' => ! \$useRealProvider")
            && str_contains($view, 'name="provider_mode"')
            && str_contains($view, '真實模型');

        return [
            'name' => 'AI Search real mode entry',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Search can send per-query mock_mode=false without changing .env.'
                : 'AI Search real provider mode is not fully wired.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkStylistRealModeEntry(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/stylist.blade.php'));
        $service = $this->readFile(base_path('app/Services/StylistTextGenerationService.php'));
        $ready = str_contains($controller, 'provider_mode')
            && str_contains($controller, "'mock_mode' => ! \$useRealProvider")
            && str_contains($view, 'name="provider_mode"')
            && str_contains($service, 'GEMINI_API_KEY_MISSING');

        return [
            'name' => 'AI Stylist real mode entry',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Stylist can attempt Gemini and safely fallback when credentials are missing.'
                : 'AI Stylist real provider mode is not fully wired.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiServiceHealth(): array
    {
        $url = rtrim((string) config('ai.service_url'), '/') . '/health';

        try {
            $response = Http::timeout(3)->get($url);
        } catch (\Throwable $exception) {
            return [
                'name' => 'AI service health',
                'status' => 'warn',
                'message' => 'AI service is not reachable now; start it before real provider manual QA.',
                'required' => false,
            ];
        }

        return [
            'name' => 'AI service health',
            'status' => $response->successful() ? 'pass' : 'warn',
            'message' => $response->successful()
                ? 'AI service health endpoint responded successfully.'
                : 'AI service health returned HTTP ' . $response->status() . '.',
            'required' => false,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkQdrantPreflight(): array
    {
        $serviceUrl = rtrim((string) config('ai.service_url'), '/');
        $token = (string) config('ai.internal_token');

        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Internal-AI-Token' => $token])
                ->get($serviceUrl . '/ai/vector-store/preflight?check_connection=true');
        } catch (\Throwable $exception) {
            return [
                'name' => 'Qdrant preflight',
                'status' => 'warn',
                'message' => 'Qdrant preflight could not run; start AI service and Qdrant before real search QA.',
                'required' => false,
            ];
        }

        $payload = $response->json();
        $ready = $response->successful() && (($payload['status'] ?? null) === 'ready');

        return [
            'name' => 'Qdrant preflight',
            'status' => $ready ? 'pass' : 'warn',
            'message' => $ready
                ? 'Qdrant preflight is ready.'
                : 'Qdrant preflight is not ready; real AI Search will use fallback or require Qdrant startup.',
            'required' => false,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkGeminiConfig(): array
    {
        $apiKey = (string) config('ai.gemini_api_key', '');
        $model = (string) config('ai.gemini_text_model', '');

        if ($model === '') {
            return [
                'name' => 'Gemini config',
                'status' => 'fail',
                'message' => 'GEMINI_TEXT_MODEL is empty.',
                'required' => true,
            ];
        }

        return [
            'name' => 'Gemini config',
            'status' => $apiKey === '' ? 'warn' : 'pass',
            'message' => $apiKey === ''
                ? 'GEMINI_API_KEY is empty; AI Stylist real mode will record GEMINI_API_KEY_MISSING and fallback.'
                : 'Gemini API key and model are configured.',
            'required' => false,
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
                'Provider matrix: %d failed / %d warnings across %d capabilities.',
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
    private function checkIgnoredLocalProviderArtifacts(): array
    {
        $lines = $this->gitStatusLines([
            base_path('tools/qdrant'),
            base_path('ai_service/models/huggingface'),
        ]);
        $listed = collect($lines)
            ->filter(fn (string $line) => trim($line) !== '')
            ->values();

        return [
            'name' => 'Local provider artifact ignore rules',
            'status' => $listed->isEmpty() ? 'pass' : 'fail',
            'message' => $listed->isEmpty()
                ? 'Qdrant runtime and Hugging Face model cache are not listed by git status.'
                : 'Provider artifacts appear in git status: ' . $listed->implode(' | '),
            'required' => true,
        ];
    }

    private function readFile(string $path): string
    {
        return file_exists($path) ? (string) file_get_contents($path) : '';
    }

    /**
     * @param array<int, string> $paths
     * @return array<int, string>
     */
    private function gitStatusLines(array $paths): array
    {
        $repoPath = str_replace('\\', '/', base_path());
        $escapedPaths = implode(' ', array_map(
            fn (string $path) => escapeshellarg(str_replace('\\', '/', $path)),
            $paths
        ));

        $process = proc_open(
            'git -C ' . escapeshellarg($repoPath) . ' status --short --untracked-files=all -- ' . $escapedPaths,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (! is_resource($process)) {
            return ['!! git status failed'];
        }

        $output = stream_get_contents($pipes[1]) ?: '';
        $errorOutput = stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        return collect(preg_split('/\R/', $output . "\n" . $errorOutput) ?: [])
            ->map(fn (string $line) => rtrim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
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
