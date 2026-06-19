<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class CoreFeatureReadinessService
{
    public function __construct(
        private readonly ModelProviderMatrixService $providerMatrix,
    ) {
    }

    /**
     * @return array{ok: bool, failed: int, warnings: int, features: array<int, array<string, mixed>>}
     */
    public function summary(): array
    {
        $features = [
            $this->closetFeature(),
            $this->aiSearchFeature(),
            $this->aiStylistFeature(),
            $this->tryOnFeature(),
            $this->runwayVideoFeature(),
            $this->digitalTwinFeature(),
        ];

        $failed = collect($features)->where('status', 'fail')->count();
        $warnings = collect($features)->where('status', 'warn')->count();

        return [
            'ok' => $failed === 0,
            'failed' => $failed,
            'warnings' => $warnings,
            'features' => $features,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function closetFeature(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));

        return $this->feature('My Closet', [
            $this->signal('upload route', Route::has('closet.store')),
            $this->signal('show route', Route::has('closet.show')),
            $this->signal('reanalyze route', Route::has('closet.reanalyze')),
            $this->signal('reembed route', Route::has('closet.reembed')),
            $this->signal('attribute analysis adapter', str_contains($controller, 'analyzeAttributes')),
            $this->signal('image embedding adapter', str_contains($controller, 'embedImage')),
            $this->signal('embedding persistence', str_contains($controller, 'saveImageEmbeddingResult')),
        ], 'Upload, analysis, reanalysis, and image embedding rebuild are wired.');
    }

    /**
     * @return array<string, mixed>
     */
    private function aiSearchFeature(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $view = $this->readFile(base_path('resources/views/closet/search.blade.php'));

        return $this->feature('AI Search', [
            $this->signal('search route', Route::has('closet.search')),
            $this->signal('real provider mode', str_contains($controller, "providerMode === 'real'")),
            $this->signal('text embedding adapter', str_contains($controller, 'embedText')),
            $this->signal('vector search adapter', str_contains($controller, 'searchSimilar')),
            $this->signal('search quality hints', str_contains($controller, 'buildSearchAcceptance') && str_contains($view, 'searchAcceptance')),
            $this->signal('index rebuild entry', Route::has('closet.reembed')),
        ], 'Real vector search, per-item rebuild, and visible search-quality hints are wired.');
    }

    /**
     * @return array<string, mixed>
     */
    private function aiStylistFeature(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $service = $this->readFile(base_path('app/Services/StylistTextGenerationService.php'));

        return $this->feature('AI Stylist', [
            $this->signal('stylist route', Route::has('closet.stylist')),
            $this->signal('generate route', Route::has('closet.stylist.generate')),
            $this->signal('Gemini adapter', str_contains($service, 'generateWithGemini')),
            $this->signal('save outfit route', Route::has('closet.stylist.outfit-log')),
            $this->signal('feedback route', Route::has('closet.stylist.feedback')),
            $this->signal('digital twin context', str_contains($controller, 'latestDigitalTwinStylistContext')),
        ], 'Gemini recommendation, saved outfit logs, feedback, and digital twin context are wired.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tryOnFeature(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $providerStatus = $this->providerStatus('virtual-tryon-provider-v1');

        return $this->feature('Try-on', [
            $this->signal('try-on page route', Route::has('closet.tryon')),
            $this->signal('try-on submit route', Route::has('closet.tryon.store')),
            $this->signal('pose quality job', str_contains($controller, 'analyzePose') && str_contains($controller, 'pose_analysis')),
            $this->signal('external try-on adapter', str_contains($controller, 'generateTryOn')),
            $this->signal('job status polling route', Route::has('ai-jobs.show')),
            $this->signal('job retry route', Route::has('ai-jobs.retry')),
            $this->signal('live provider enabled', $providerStatus === 'pass', false),
        ], 'Pose job, external try-on provider attempt, status polling, and retry marker are wired.');
    }

    /**
     * @return array<string, mixed>
     */
    private function runwayVideoFeature(): array
    {
        $controller = $this->readFile(base_path('app/Http/Controllers/WorkspaceController.php'));
        $providerStatus = $this->providerStatus('video-generation-provider-v1');

        return $this->feature('Runway Video', [
            $this->signal('workspace route', Route::has('workspace.show')),
            $this->signal('video task route', Route::has('workspace.runway-video.store')),
            $this->signal('external video adapter', str_contains($controller, 'generateVideo')),
            $this->signal('provider job metadata', str_contains($controller, 'provider_attempt')),
            $this->signal('polling route', Route::has('ai-jobs.show')),
            $this->signal('saved video URL field', str_contains($controller, 'video_url')),
            $this->signal('retry route', Route::has('ai-jobs.retry')),
            $this->signal('live provider enabled', $providerStatus === 'pass', false),
        ], 'Video task submission, polling, provider metadata, saved video URL, and retry marker are wired.');
    }

    /**
     * @return array<string, mixed>
     */
    private function digitalTwinFeature(): array
    {
        $workspaceController = $this->readFile(base_path('app/Http/Controllers/WorkspaceController.php'));
        $closetController = $this->readFile(base_path('app/Http/Controllers/ClosetController.php'));
        $providerStatus = $this->providerStatus('digital-twin-avatar-provider-v1');

        return $this->feature('Digital Twin', [
            $this->signal('profile route', Route::has('workspace.digital-twin.store')),
            $this->signal('closet analysis route', Route::has('workspace.digital-twin.analyze-closet')),
            $this->signal('avatar provider adapter', str_contains($workspaceController, 'generateDigitalTwin')),
            $this->signal('provider avatar URL', str_contains($workspaceController, 'provider_avatar')),
            $this->signal('closet profile analysis', str_contains($workspaceController, 'digital_twin_style_analysis')),
            $this->signal('stylist integration', str_contains($closetController, 'latestDigitalTwinStylistContext')),
            $this->signal('job polling route', Route::has('ai-jobs.show')),
            $this->signal('live provider enabled', $providerStatus === 'pass', false),
        ], 'Profile, avatar provider attempt, closet analysis, stylist context, and polling are wired.');
    }

    /**
     * @param array<int, array{name: string, status: string, required: bool}> $signals
     * @return array<string, mixed>
     */
    private function feature(string $name, array $signals, string $message): array
    {
        $missingRequired = collect($signals)
            ->where('required', true)
            ->where('status', 'missing')
            ->count();
        $missingOptional = collect($signals)
            ->where('required', false)
            ->where('status', 'missing')
            ->count();

        return [
            'name' => $name,
            'status' => $missingRequired > 0 ? 'fail' : ($missingOptional > 0 ? 'warn' : 'pass'),
            'message' => $message,
            'signals' => $signals,
        ];
    }

    /**
     * @return array{name: string, status: string, required: bool}
     */
    private function signal(string $name, bool $ready, bool $required = true): array
    {
        return [
            'name' => $name,
            'status' => $ready ? 'ready' : 'missing',
            'required' => $required,
        ];
    }

    private function providerStatus(string $adapter): string
    {
        $summary = $this->providerMatrix->summary();
        $provider = collect($summary['providers'])->firstWhere('adapter', $adapter);

        return is_array($provider) ? (string) $provider['status'] : 'missing';
    }

    private function readFile(string $path): string
    {
        if (! is_file($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
}
