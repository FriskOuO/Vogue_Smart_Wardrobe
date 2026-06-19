<?php

namespace Tests\Feature;

use App\Services\UploadScopeReviewService;
use Tests\TestCase;

class UploadScopeReviewTest extends TestCase
{
    public function test_upload_scope_review_groups_status_fixture(): void
    {
        $summary = app(UploadScopeReviewService::class)->summary([
            ' M ai_service/routes/ai_routes.py',
            '?? app/Services/RealModeAcceptanceService.php',
            ' M resources/views/closet/index.blade.php',
            '?? tests/Feature/RealModeAcceptanceTest.php',
            '?? docs/model-integration-readiness.md',
            ' D database/migrations/2026_04_22_161722_create_telescope_entries_table.php',
            '?? public/images/demo/white-shirt.jpg',
            ' M .gitignore',
        ]);

        $this->assertSame(8, $summary['total']);
        $this->assertSame(1, $summary['groups']['AI service']);
        $this->assertSame(1, $summary['groups']['Laravel backend']);
        $this->assertSame(1, $summary['groups']['Views and UI']);
        $this->assertSame(1, $summary['groups']['Tests']);
        $this->assertSame(1, $summary['groups']['Docs']);
        $this->assertSame(1, $summary['groups']['Database migrations']);
        $this->assertSame(1, $summary['groups']['Assets']);
        $this->assertSame(1, $summary['groups']['Config and scripts']);
        $this->assertSame(
            'ai_service/routes/ai_routes.py',
            $summary['commit_group_entries']['ai-service-adapter-contracts'][0]['path']
        );
        $this->assertSame(
            'modified',
            $summary['commit_group_entries']['ai-service-adapter-contracts'][0]['status']
        );
        $this->assertSame(
            'database/migrations/2026_04_22_161722_create_telescope_entries_table.php',
            $summary['commit_group_entries']['telescope-duplicate-migration-cleanup'][0]['path']
        );
        $this->assertSame(
            'deleted',
            $summary['commit_group_entries']['telescope-duplicate-migration-cleanup'][0]['status']
        );
        $this->assertContains(
            'Confirm Telescope cleanup: keep 2026_04_22_161640 and delete 2026_04_22_161722.',
            $summary['confirmation_items']
        );
    }

    public function test_upload_scope_review_flags_env_and_large_model_risks(): void
    {
        $summary = app(UploadScopeReviewService::class)->summary([
            ' M .env',
            '?? ai_service/models/clip/model.gguf',
        ]);

        $this->assertCount(2, $summary['risks']);
        $this->assertFalse($summary['ready_for_upload']);
    }

    public function test_manual_acceptance_test_is_grouped_with_provider_gates(): void
    {
        $summary = app(UploadScopeReviewService::class)->summary([
            '?? tests/Feature/ManualAcceptanceTest.php',
            '?? app/Services/ModelProviderMatrixService.php',
            '?? app/Services/ProductionReadinessService.php',
            '?? tests/Feature/ModelProviderMatrixTest.php',
            '?? tests/Feature/ProductionReadinessTest.php',
        ]);

        $this->assertSame(0, $summary['commit_groups']['needs-manual-review']);
        $this->assertSame(5, $summary['commit_groups']['demo-readiness-provider-gates']);
        $this->assertSame(
            'tests/Feature/ManualAcceptanceTest.php',
            $summary['commit_group_entries']['demo-readiness-provider-gates'][0]['path']
        );
    }

    public function test_upload_scope_artisan_command_runs(): void
    {
        $this->artisan('vogueai:upload-scope')
            ->expectsOutput('VogueAI upload scope review')
            ->expectsOutput('Suggested commit group file lists:')
            ->assertExitCode(0);
    }
}
