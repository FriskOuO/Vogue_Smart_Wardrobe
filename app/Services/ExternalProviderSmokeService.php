<?php

namespace App\Services;

class ExternalProviderSmokeService
{
    public function __construct(
        private readonly ExternalModelProviderService $externalModelProvider,
    ) {
    }

    /**
     * @return array{ok: bool, failed: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(?string $only = null): array
    {
        $availableChecks = [
            'tryon' => fn () => $this->tryOnSmoke(),
            'video' => fn () => $this->videoSmoke(),
            'digital-twin' => fn () => $this->digitalTwinSmoke(),
        ];

        $checks = collect($availableChecks)
            ->when($only !== null, fn ($collection) => $collection->only($only))
            ->map(fn (callable $check) => $check())
            ->values()
            ->all();

        if ($only !== null && empty($checks)) {
            $checks = [[
                'name' => 'External provider smoke selector',
                'status' => 'fail',
                'message' => 'Unknown provider selector. Use tryon, video, or digital-twin.',
                'details' => [
                    'requested' => $only,
                    'available' => array_keys($availableChecks),
                ],
            ]];
        }

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
     * @return array<string, mixed>
     */
    private function tryOnSmoke(): array
    {
        $result = $this->externalModelProvider->generateTryOn([
            'request_id' => 'external_smoke_tryon_' . now()->format('YmdHis'),
            'user_id' => 0,
            'person_image_url' => asset('images/demo/white-shirt.jpg'),
            'clothing_image_url' => asset('images/demo/white-shirt.jpg'),
            'pose_analysis' => [
                'pose_quality_status' => 'smoke_test',
            ],
        ]);

        return $this->check('Try-on provider', $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function videoSmoke(): array
    {
        $result = $this->externalModelProvider->generateVideo([
            'request_id' => 'external_smoke_video_' . now()->format('YmdHis'),
            'user_id' => 0,
            'clothing_id' => 0,
            'image_url' => asset('images/demo/white-shirt.jpg'),
            'prompt' => 'Generate a short vertical runway video smoke test for a white shirt.',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 4,
        ]);

        return $this->check('Runway / Veo provider', $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function digitalTwinSmoke(): array
    {
        $result = $this->externalModelProvider->generateDigitalTwin([
            'request_id' => 'external_smoke_digital_twin_' . now()->format('YmdHis'),
            'user_id' => 0,
            'profile' => [
                'height_cm' => 170,
                'style_preference' => 'minimal',
                'common_occasion' => 'daily',
            ],
        ]);

        return $this->check('Digital Twin avatar provider', $result);
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function check(string $name, array $result): array
    {
        $ready = in_array($result['status'] ?? null, ['ready', 'processing', 'success'], true);

        return [
            'name' => $name,
            'status' => $ready ? 'pass' : 'warn',
            'message' => $ready
                ? $name . ' returned a live provider response.'
                : $name . ' adapter is wired but live provider did not return ready.',
            'details' => $result,
        ];
    }
}
