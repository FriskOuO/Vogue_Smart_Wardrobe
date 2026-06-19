<?php

namespace Tests\Feature;

use App\Services\GithubReadinessService;
use Tests\TestCase;

class GithubReadinessTest extends TestCase
{
    public function test_github_readiness_passes_for_clean_status_fixture(): void
    {
        $summary = app(GithubReadinessService::class)->summary([]);

        $this->assertTrue($summary['ok']);
        $this->assertSame(0, $summary['blockers']);
        $this->assertSame(0, $summary['warnings']);
    }

    public function test_github_readiness_blocks_dirty_telescope_deletion_fixture(): void
    {
        $summary = app(GithubReadinessService::class)->summary([
            ' M README.md',
            ' D database/migrations/2026_04_22_161722_create_telescope_entries_table.php',
            '?? ai_service/services/vector_store_service.py',
        ]);

        $this->assertFalse($summary['ok']);
        $this->assertSame(2, $summary['blockers']);
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'Telescope migration deletion'
                    && $check['status'] === 'block'
            )
        );
    }

    public function test_github_readiness_parses_staged_statuses_and_warns_without_counting_git_warnings(): void
    {
        $summary = app(GithubReadinessService::class)->summary([
            'warning: could not open directory \'.pytest_cache/\': Permission denied',
            'D  database/migrations/2026_04_22_161722_create_telescope_entries_table.php',
            'AM .env',
            '?? ai_service/models/clip/model.gguf',
        ]);

        $this->assertFalse($summary['ok']);
        $this->assertSame(4, $summary['blockers']);
        $this->assertSame(1, $summary['warnings']);
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'Worktree review'
                    && str_contains($check['message'], '3 changed/untracked entries')
            )
        );
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'Local .env safety'
                    && $check['status'] === 'block'
            )
        );
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'Large model artifacts'
                    && $check['status'] === 'block'
            )
        );
        $this->assertTrue(
            collect($summary['checks'])->contains(
                fn (array $check) => $check['name'] === 'Git status warnings'
                    && $check['status'] === 'warn'
            )
        );
    }

    public function test_github_readiness_artisan_command_runs_and_blocks_current_dirty_worktree(): void
    {
        $this->artisan('vogueai:github-check')
            ->expectsOutput('VogueAI GitHub readiness check')
            ->assertExitCode(1);
    }
}
