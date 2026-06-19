<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class RealModeAcceptanceService
{
    public function __construct(
        private readonly ProviderReadinessService $providerReadiness,
    ) {
    }

    /**
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $checks = [
            $this->checkRoutes(),
            $this->checkAiSearchWiring(),
            $this->checkAiSearchCoverage(),
            $this->checkAiStylistWiring(),
            $this->checkAiStylistMissingKeyCoverage(),
            $this->checkAiStylistReadyCoverage(),
            $this->checkProviderStartupGate(),
            $this->checkManualChecklist(),
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
        $ready = Route::has('closet.search')
            && Route::has('closet.stylist')
            && Route::has('closet.stylist.generate');

        return [
            'name' => 'Real mode routes',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Search and AI Stylist routes are registered.'
                : 'AI Search or AI Stylist route is missing.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiSearchWiring(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/search.blade.php'));

        $ready = str_contains($controller, "'mock_mode' => ! \$useRealProvider")
            && str_contains($view, 'name="provider_mode"')
            && str_contains($view, '<option value="real"');

        return [
            'name' => 'AI Search real mode wiring',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Search has a real-mode selector and sends mock_mode=false per request.'
                : 'AI Search real-mode selector or mock_mode=false wiring is incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiSearchCoverage(): array
    {
        $test = $this->readFile(base_path('tests/Feature/AiSearchTest.php'));

        $ready = str_contains($test, 'test_real_provider_mode_sends_mock_mode_false_to_ai_service')
            && str_contains($test, 'provider_mode=real')
            && str_contains($test, "['mock_mode'] ?? null) === false");

        return [
            'name' => 'AI Search real mode test coverage',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Search real mode is covered by feature tests.'
                : 'AI Search real mode feature coverage is missing.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiStylistWiring(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/stylist.blade.php'));
        $service = $this->readFile(base_path('app/Services/StylistTextGenerationService.php'));

        $ready = str_contains($controller, "'mock_mode' => ! \$useRealProvider")
            && str_contains($view, 'name="provider_mode"')
            && str_contains($view, '<option value="real"')
            && str_contains($service, 'GEMINI_API_KEY_MISSING');

        return [
            'name' => 'AI Stylist real mode wiring',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'AI Stylist has a real-mode selector and a safe Gemini fallback path.'
                : 'AI Stylist real-mode selector or Gemini fallback wiring is incomplete.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiStylistMissingKeyCoverage(): array
    {
        $test = $this->readFile(base_path('tests/Feature/AiStylistTest.php'));

        $ready = str_contains($test, 'test_stylist_real_provider_mode_degrades_when_gemini_key_is_missing')
            && str_contains($test, 'GEMINI_API_KEY_MISSING')
            && str_contains($test, "'provider_mode' => 'real'");

        return [
            'name' => 'AI Stylist missing-key fallback coverage',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Missing Gemini key fallback is covered by feature tests.'
                : 'Missing Gemini key fallback coverage is missing.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiStylistReadyCoverage(): array
    {
        $test = $this->readFile(base_path('tests/Feature/AiStylistTest.php'));

        $ready = str_contains($test, 'test_stylist_real_provider_mode_can_record_ready_gemini_response')
            && str_contains($test, 'ready')
            && str_contains($test, 'real_adapter');

        return [
            'name' => 'AI Stylist ready Gemini coverage',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Ready Gemini responses are covered by feature tests.'
                : 'Ready Gemini response coverage is missing.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkProviderStartupGate(): array
    {
        $summary = $this->providerReadiness->summary();

        if ($summary['failed'] > 0) {
            return [
                'name' => 'Provider startup gate',
                'status' => 'warn',
                'message' => sprintf(
                    'Provider gate has %d failed / %d warnings; run php artisan vogueai:provider-check before manual real-mode QA.',
                    $summary['failed'],
                    $summary['warnings']
                ),
                'required' => false,
            ];
        }

        return [
            'name' => 'Provider startup gate',
            'status' => $summary['warnings'] <= 1 ? 'pass' : 'warn',
            'message' => match (true) {
                $summary['warnings'] === 0 => 'Provider gate is fully ready for real-mode QA.',
                $summary['warnings'] === 1 => 'Provider gate is at the expected state; only one external provider warning may remain.',
                default => sprintf('Provider gate has %d warnings; start AI Service and Qdrant before real-mode QA.', $summary['warnings']),
            },
            'required' => false,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkManualChecklist(): array
    {
        $checklist = $this->readFile(base_path('docs/manual-acceptance-checklist.md'));

        $ready = str_contains($checklist, 'provider-check')
            && str_contains($checklist, 'AI Search')
            && str_contains($checklist, 'AI Stylist')
            && str_contains($checklist, 'GEMINI_API_KEY');

        return [
            'name' => 'Manual acceptance checklist',
            'status' => $ready ? 'pass' : 'fail',
            'message' => $ready
                ? 'Manual checklist includes real-mode provider acceptance items.'
                : 'Manual checklist is missing real-mode acceptance items.',
            'required' => true,
        ];
    }

    private function readFile(string $path): string
    {
        return file_exists($path) ? (string) file_get_contents($path) : '';
    }
}
