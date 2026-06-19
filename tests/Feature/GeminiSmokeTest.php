<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiSmokeTest extends TestCase
{
    public function test_gemini_smoke_fails_safely_when_key_is_missing(): void
    {
        config([
            'ai.gemini_api_key' => null,
            'ai.gemini_text_model' => 'gemini-2.5-flash',
        ]);

        $this->artisan('vogueai:gemini-smoke')
            ->expectsOutput('VogueAI Gemini smoke check')
            ->expectsOutput('Status: degraded')
            ->expectsOutput('Error code: GEMINI_API_KEY_MISSING')
            ->assertExitCode(1);
    }

    public function test_gemini_smoke_passes_with_structured_response(): void
    {
        config([
            'ai.gemini_api_key' => 'test-gemini-key',
            'ai.gemini_text_model' => 'gemini-2.5-flash',
            'ai.gemini_api_base_url' => 'https://generativelanguage.googleapis.com',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Gemini Smoke Outfit',
                                        'summary' => 'A concise Gemini smoke response.',
                                        'styling_tips' => [
                                            'Use the white shirt as the clean base.',
                                            'Layer the navy blazer for polish.',
                                        ],
                                        'reasoning_notes' => [
                                            'Uses only selected closet items.',
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('vogueai:gemini-smoke')
            ->expectsOutput('VogueAI Gemini smoke check')
            ->expectsOutput('Status: ready')
            ->expectsOutput('Mode: real_adapter')
            ->expectsOutput('Fallback active: no')
            ->expectsOutput('Summary: Gemini API smoke passed.')
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && str_contains($request->url(), '/v1beta/models/gemini-2.5-flash:generateContent'));
    }
}
