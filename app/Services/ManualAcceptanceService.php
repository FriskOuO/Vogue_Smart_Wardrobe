<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class ManualAcceptanceService
{
    public function __construct(
        private readonly ProviderReadinessService $providerReadiness,
        private readonly RealModeAcceptanceService $realModeAcceptance,
    ) {
    }

    /**
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $checks = [
            $this->checkRoutes(),
            $this->checkCriticalFlowVisibility(),
            $this->checkManualQaCockpitSignals(),
            $this->checkSearchAcceptanceSignals(),
            $this->checkStylistAcceptanceSignals(),
            $this->checkTryOnAcceptanceSignals(),
            $this->checkWorkspaceAcceptanceSignals(),
            $this->checkManualDocs(),
            $this->checkAcceptanceTests(),
            $this->checkLaravelHttp(),
            $this->checkProviderGate(),
            $this->checkRealModeGate(),
            $this->checkGithubUploadStillManual(),
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
    private function checkRoutes(): array
    {
        $routes = [
            'closet.stylist',
            'closet.stylist.generate',
            'closet.tryon',
            'closet.tryon.store',
            'closet.search',
            'closet.hub',
            'workspace.show',
            'workspace.runway-video.store',
            'workspace.digital-twin.store',
            'workspace.digital-twin.analyze-closet',
        ];

        $missing = collect($routes)
            ->reject(fn (string $route) => Route::has($route))
            ->values();

        return [
            'name' => 'Manual QA routes',
            'status' => $missing->isEmpty() ? 'pass' : 'fail',
            'message' => $missing->isEmpty()
                ? 'Stylist, Try-on, AI Search, Workspace, and Smart Closet Hub routes are registered.'
                : 'Missing routes: ' . $missing->implode(', '),
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkCriticalFlowVisibility(): array
    {
        $stylistView = $this->readFile(base_path('resources/views/closet/stylist.blade.php'));
        $tryOnView = $this->readFile(base_path('resources/views/closet/tryon.blade.php'));
        $searchView = $this->readFile(base_path('resources/views/closet/search.blade.php'));
        $workspaceView = $this->readFile(base_path('resources/views/workspace/show.blade.php'));
        $hubView = $this->readFile(base_path('resources/views/closet/hub.blade.php'));
        $css = $this->readFile(base_path('resources/css/app.css'));

        $ready = str_contains($stylistView, 'vogue-critical-flow')
            && str_contains($tryOnView, 'vogue-critical-flow')
            && str_contains($searchView, 'vogue-critical-flow')
            && str_contains($workspaceView, 'vogue-critical-flow')
            && str_contains($hubView, 'vogue-critical-flow')
            && str_contains($css, '.vogue-critical-flow');

        return [
            'name' => 'Critical flow visibility',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Stylist, Try-on, AI Search, Workspace, and Smart Closet Hub forms are protected from reveal-animation blank states.'
                : 'Critical flow fallback visibility is incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkManualQaCockpitSignals(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/hub.blade.php'));

        $ready = str_contains($controller, 'manualQaItems')
            && str_contains($view, '人工驗收總控台')
            && str_contains($view, 'manualQaItems')
            && str_contains($controller, '真實搜尋可人工驗收')
            && str_contains($controller, '最新伸展台任務可人工驗收')
            && str_contains($controller, '最新數位分身任務可人工驗收');

        return [
            'name' => 'Manual QA cockpit signals',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Smart Closet Hub lists the manual QA cockpit and expected acceptance signals.'
                : 'Manual QA cockpit signals are incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkSearchAcceptanceSignals(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/search.blade.php'));

        $ready = str_contains($controller, 'buildSearchAcceptance')
            && str_contains($controller, '真實搜尋可人工驗收')
            && str_contains($controller, 'fallback 未啟用')
            && str_contains($view, '搜尋驗收狀態')
            && str_contains($view, 'searchAcceptance');

        return [
            'name' => 'AI Search acceptance signals',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Search exposes real Qdrant / CLIP readiness and fallback state for manual QA.'
                : 'AI Search manual acceptance signals are incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkStylistAcceptanceSignals(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/stylist.blade.php'));

        $ready = str_contains($controller, 'ready / real_adapter')
            && str_contains($controller, '安全 fallback')
            && str_contains($view, 'Gemini 可人工測試')
            && str_contains($view, 'Gemini 文字轉接器')
            && str_contains($view, 'fallback_active');

        return [
            'name' => 'Stylist acceptance signals',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Stylist real-model results expose ready / real_adapter and fallback status for manual QA.'
                : 'Stylist manual acceptance signals are incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkTryOnAcceptanceSignals(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/tryon.blade.php'));

        $ready = str_contains($controller, 'generateTryOn')
            && str_contains($controller, 'tryon_provider_attempt')
            && str_contains($view, 'tryon_provider_attempt')
            && str_contains($view, 'tryon_provider_status')
            && str_contains($view, 'tryon_output_url')
            && str_contains($view, 'data-tryon-auto-poll')
            && str_contains($view, "route('ai-jobs.tryon-status'");

        return [
            'name' => 'Try-on acceptance signals',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Try-on provider task status, automatic polling, success output, and failure states are visible for manual QA.'
                : 'Try-on manual acceptance signals are incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkWorkspaceAcceptanceSignals(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/WorkspaceController.php'));
        $view = $this->readFile(base_path('resources/views/workspace/show.blade.php'));

        $ready = str_contains($controller, '伸展台影片 L2 預覽任務已建立，可人工驗收')
            && str_contains($controller, '數位分身 L2 衣櫥風格分析已建立，可人工驗收')
            && str_contains($view, '最新伸展台任務可人工驗收')
            && str_contains($view, '最新數位分身任務可人工驗收')
            && str_contains($view, '預覽狀態');

        return [
            'name' => 'Workspace acceptance signals',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Runway Video and Digital Twin latest-task acceptance states are visible for manual QA.'
                : 'Workspace manual acceptance signals are incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkManualDocs(): array
    {
        $progress = $this->readFile(base_path('docs/vogueai-core-progress.md'));
        $checklist = $this->readFile(base_path('docs/manual-acceptance-checklist.md'));

        $ready = str_contains($progress, '人工驗收總控台完成')
            && str_contains($progress, 'Workspace 亮點模組人工驗收可視化完成')
            && str_contains($progress, 'AI Search 真實模型人工驗收可視化完成')
            && str_contains($progress, 'Try-on L1 新任務人工驗收可視化完成')
            && str_contains($progress, 'Stylist 真實模型結果可視化驗收完成')
            && str_contains($checklist, 'AI Search')
            && str_contains($checklist, 'AI Stylist')
            && str_contains($checklist, 'Try-on')
            && str_contains($checklist, 'Digital Twin')
            && str_contains($checklist, 'Runway Video');

        return [
            'name' => 'Manual acceptance docs',
            'status' => $ready ? 'pass' : 'warn',
            'message' => $ready
                ? 'Core progress and manual checklist include current Stylist / Try-on acceptance context.'
                : 'Manual acceptance docs may need a progress/checklist refresh.',
            'required' => false,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAcceptanceTests(): array
    {
        $stylistTest = $this->readFile(base_path('tests/Feature/AiStylistTest.php'));
        $tryOnTest = $this->readFile(base_path('tests/Feature/AiJobsL1Test.php'));
        $searchTest = $this->readFile(base_path('tests/Feature/AiSearchTest.php'));
        $smartClosetTest = $this->readFile(base_path('tests/Feature/SmartClosetTest.php'));

        $ready = str_contains($stylistTest, 'real_adapter / ready')
            && str_contains($stylistTest, 'fallback_active: false')
            && str_contains($tryOnTest, '最新任務可人工驗收')
            && str_contains($tryOnTest, '最新任務失敗')
            && str_contains($tryOnTest, '最新伸展台任務可人工驗收')
            && str_contains($tryOnTest, '最新數位分身任務可人工驗收')
            && str_contains($searchTest, '真實搜尋可人工驗收')
            && str_contains($searchTest, 'fallback 未啟用')
            && str_contains($smartClosetTest, '人工驗收總控台');

        return [
            'name' => 'Manual QA test coverage',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Manual QA cockpit, AI Search, Stylist, Try-on, Runway, and Digital Twin acceptance signals are covered by feature tests.'
                : 'Manual acceptance signal tests are incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkLaravelHttp(): array
    {
        $baseUrls = collect([
            rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/'),
            'http://127.0.0.1:8000',
        ])->unique()->values();

        foreach ($baseUrls as $baseUrl) {
            try {
                $response = Http::timeout(3)->get($baseUrl . '/closet/try-on');
            } catch (\Throwable) {
                continue;
            }

            if ($response->successful()) {
                return [
                    'name' => 'Laravel local HTTP',
                    'status' => 'pass',
                    'message' => 'Laravel local server responded for ' . $baseUrl . '/closet/try-on.',
                    'required' => false,
                ];
            }
        }

        return [
            'name' => 'Laravel local HTTP',
            'status' => 'warn',
            'message' => 'Laravel local server is not reachable; start php artisan serve before browser QA.',
            'required' => false,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkProviderGate(): array
    {
        $summary = $this->providerReadiness->summary();

        return [
            'name' => 'Provider gate',
            'status' => $summary['failed'] > 0 ? 'fail' : ($summary['warnings'] > 0 ? 'warn' : 'pass'),
            'message' => sprintf('provider-check summary: %d failed, %d warnings.', $summary['failed'], $summary['warnings']),
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkRealModeGate(): array
    {
        $summary = $this->realModeAcceptance->summary();

        return [
            'name' => 'Real-mode gate',
            'status' => $summary['failed'] > 0 ? 'fail' : ($summary['warnings'] > 0 ? 'warn' : 'pass'),
            'message' => sprintf('real-mode-check summary: %d failed, %d warnings.', $summary['failed'], $summary['warnings']),
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkGithubUploadStillManual(): array
    {
        $gitStatus = $this->gitStatusLines();
        $envListed = collect($gitStatus)->contains(fn (string $line) => str_contains($line, '.env') && ! str_contains($line, '.env.example'));

        return [
            'name' => 'GitHub upload state',
            'status' => $envListed ? 'fail' : 'pass',
            'message' => $envListed
                ? '.env appears in git status; do not upload until secrets are removed.'
                : 'Review only: no staging, commit, push, or PR is required for manual QA.',
            'required' => true,
        ];
    }

    private function readFile(string $path): string
    {
        return file_exists($path) ? (string) file_get_contents($path) : '';
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
}
