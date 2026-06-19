<?php

namespace App\Services;

class DemoReadinessService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function checks(): array
    {
        return [
            $this->checkPath('Laravel artisan', base_path('artisan'), true),
            $this->checkPath('Environment file', base_path('.env'), true),
            $this->checkPath('AI service folder', base_path('ai_service'), true),
            $this->checkPath('AI service venv python', base_path('ai_service/.venv/Scripts/python.exe'), true),
            $this->checkPath('AI service requirements', base_path('ai_service/requirements.txt'), true),
            $this->checkPath('Optional ML requirements', base_path('ai_service/requirements-ml.txt'), false),
            $this->checkPath('Core progress doc', base_path('docs/vogueai-core-progress.md'), true),
            $this->checkDemoDataCommand(),
            $this->checkGithubCheckCommand(),
            $this->checkAiServiceConfig(),
            $this->checkAiMockMode(),
            $this->checkTelescopeMigrationRisk(),
        ];
    }

    /**
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $checks = $this->checks();
        $failed = collect($checks)
            ->where('status', 'fail')
            ->count();
        $warnings = collect($checks)
            ->where('status', 'warn')
            ->count();

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
    private function checkAiServiceConfig(): array
    {
        $serviceUrl = (string) config('ai.service_url', '');
        $internalToken = (string) config('ai.internal_token', '');

        if ($serviceUrl === '' || $internalToken === '') {
            return [
                'name' => 'AI service config',
                'status' => 'fail',
                'message' => 'AI_SERVICE_URL or AI_INTERNAL_TOKEN is empty.',
                'required' => true,
            ];
        }

        return [
            'name' => 'AI service config',
            'status' => 'pass',
            'message' => sprintf('AI service URL configured: %s', $serviceUrl),
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkDemoDataCommand(): array
    {
        $consoleRoutes = base_path('routes/console.php');
        $isConfigured = file_exists($consoleRoutes)
            && str_contains((string) file_get_contents($consoleRoutes), 'vogueai:demo-data');

        return [
            'name' => 'Demo data command',
            'status' => $isConfigured ? 'pass' : 'fail',
            'message' => $isConfigured
                ? 'php artisan vogueai:demo-data seed|cleanup is configured.'
                : 'php artisan vogueai:demo-data seed|cleanup is not configured.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkGithubCheckCommand(): array
    {
        $consoleRoutes = base_path('routes/console.php');
        $isConfigured = file_exists($consoleRoutes)
            && str_contains((string) file_get_contents($consoleRoutes), 'vogueai:github-check');

        return [
            'name' => 'GitHub check command',
            'status' => $isConfigured ? 'pass' : 'fail',
            'message' => $isConfigured
                ? 'php artisan vogueai:github-check is configured.'
                : 'php artisan vogueai:github-check is not configured.',
            'required' => true,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkAiMockMode(): array
    {
        $mockMode = (bool) config('ai.mock_mode', true);

        return [
            'name' => 'AI mock mode',
            'status' => $mockMode ? 'pass' : 'warn',
            'message' => $mockMode
                ? 'AI_MOCK_MODE=true; demo-safe fallback mode is enabled.'
                : 'AI_MOCK_MODE=false; real adapter attempt mode is enabled. Verify ML/Qdrant dependencies before demo.',
            'required' => false,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string, required: bool}
     */
    private function checkTelescopeMigrationRisk(): array
    {
        $migrationPath = base_path('database/migrations/2026_04_22_161722_create_telescope_entries_table.php');

        if (! file_exists($migrationPath)) {
            return [
                'name' => 'Telescope migration risk',
                'status' => 'warn',
                'message' => 'Tracked Telescope migration is missing/deleted; confirm this intentional cleanup before GitHub.',
                'required' => false,
            ];
        }

        return [
            'name' => 'Telescope migration risk',
            'status' => 'pass',
            'message' => 'Tracked Telescope migration file is present.',
            'required' => false,
        ];
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
