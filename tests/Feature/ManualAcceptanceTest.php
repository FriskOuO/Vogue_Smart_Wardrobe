<?php

namespace Tests\Feature;

use App\Services\ManualAcceptanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManualAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manual_acceptance_service_checks_current_manual_qa_signals(): void
    {
        Http::fake([
            'http://localhost/closet/try-on' => Http::response('', 200),
            'http://127.0.0.1:8001/health' => Http::response(['status' => 'ok'], 200),
            'http://127.0.0.1:8001/ai/vector-store/preflight?check_connection=true' => Http::response([
                'status' => 'ready',
            ], 200),
        ]);

        $summary = app(ManualAcceptanceService::class)->summary();

        $this->assertSame(0, $summary['failed']);
        $this->assertContains(
            'Stylist acceptance signals',
            collect($summary['checks'])->pluck('name')->all()
        );
        $this->assertContains(
            'AI Search acceptance signals',
            collect($summary['checks'])->pluck('name')->all()
        );
        $this->assertContains(
            'Manual QA cockpit signals',
            collect($summary['checks'])->pluck('name')->all()
        );
        $this->assertContains(
            'Try-on acceptance signals',
            collect($summary['checks'])->pluck('name')->all()
        );
        $this->assertContains(
            'Workspace acceptance signals',
            collect($summary['checks'])->pluck('name')->all()
        );
        $this->assertSame(
            'pass',
            collect($summary['checks'])->firstWhere('name', 'Manual QA test coverage')['status']
        );
    }

    public function test_manual_acceptance_command_outputs_summary_without_git_actions(): void
    {
        Http::fake([
            'http://localhost/closet/try-on' => Http::response('', 200),
            'http://127.0.0.1:8001/health' => Http::response(['status' => 'ok'], 200),
            'http://127.0.0.1:8001/ai/vector-store/preflight?check_connection=true' => Http::response([
                'status' => 'ready',
            ], 200),
        ]);

        $this->artisan('vogueai:manual-acceptance')
            ->expectsOutput('VogueAI manual acceptance gate')
            ->expectsOutputToContain('[PASS] Manual QA cockpit signals')
            ->expectsOutputToContain('[PASS] AI Search acceptance signals')
            ->expectsOutputToContain('[PASS] Stylist acceptance signals')
            ->expectsOutputToContain('[PASS] Try-on acceptance signals')
            ->expectsOutputToContain('[PASS] Workspace acceptance signals')
            ->expectsOutputToContain('GitHub action state: review only; no staging, commit, push, or PR performed.')
            ->assertSuccessful();
    }
}
