<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\CoreFeatureReadinessService;
use App\Services\DemoDataService;
use App\Services\DemoReadinessService;
use App\Services\ExternalModelProviderService;
use App\Services\ExternalProviderSmokeService;
use App\Services\GithubReadinessService;
use App\Services\ManualAcceptanceService;
use App\Services\ModelProviderMatrixService;
use App\Services\ProductionReadinessService;
use App\Services\ProviderRuntimeSmokeService;
use App\Services\ProviderReadinessService;
use App\Services\RealModeAcceptanceService;
use App\Services\StylistTextGenerationService;
use App\Services\UploadScopeReviewService;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vogueai:demo-check', function (DemoReadinessService $readiness) {
    $summary = $readiness->summary();

    $this->line('VogueAI demo readiness check');
    $this->line('============================');

    foreach ($summary['checks'] as $check) {
        $status = strtoupper($check['status']);
        $this->line(sprintf('[%s] %s - %s', $status, $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check VogueAI demo readiness before local demo or GitHub upload');

Artisan::command('vogueai:demo-data {action=seed}', function (DemoDataService $demoData) {
    $action = (string) $this->argument('action');

    if (! in_array($action, ['seed', 'cleanup'], true)) {
        $this->error('Invalid action. Use seed or cleanup.');

        return Command::FAILURE;
    }

    $result = $action === 'seed'
        ? $demoData->seed()
        : $demoData->cleanup();

    $this->line(sprintf('VogueAI demo data %s', $action));
    $this->line('========================');

    foreach ($result as $key => $value) {
        $this->line(sprintf('%s: %s', $key, is_scalar($value) ? (string) $value : json_encode($value)));
    }

    return Command::SUCCESS;
})->purpose('Seed or cleanup the fixed VogueAI demo account and demo wardrobe data');

Artisan::command('vogueai:github-check', function (GithubReadinessService $readiness) {
    $summary = $readiness->summary();

    $this->line('VogueAI GitHub readiness check');
    $this->line('==============================');

    foreach ($summary['checks'] as $check) {
        $status = strtoupper($check['status']);
        $this->line(sprintf('[%s] %s - %s', $status, $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d blockers, %d warnings',
        $summary['blockers'],
        $summary['warnings']
    ));

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check local repository risks before staging, committing, or pushing to GitHub');

Artisan::command('vogueai:provider-check', function (ProviderReadinessService $readiness) {
    $summary = $readiness->summary();

    $this->line('VogueAI provider readiness check');
    $this->line('================================');

    foreach ($summary['checks'] as $check) {
        $status = strtoupper($check['status']);
        $this->line(sprintf('[%s] %s - %s', $status, $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check VogueAI real provider readiness before manual QA or external model activation');

Artisan::command('vogueai:provider-matrix', function (ModelProviderMatrixService $matrix) {
    $summary = $matrix->summary();

    $this->line('VogueAI model provider matrix');
    $this->line('=============================');

    foreach ($summary['providers'] as $provider) {
        $this->line(sprintf(
            '[%s] %s - %s / %s / %s',
            strtoupper($provider['status']),
            $provider['capability'],
            $provider['target_provider'],
            $provider['target_model'],
            $provider['adapter']
        ));
        $this->line('      ' . $provider['message']);
        $this->line('      config: ' . implode(', ', $provider['config_keys']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    $this->line('');
    $this->line('Secrets: API key values are intentionally never printed.');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('List all model provider capabilities, target models, adapters, and readiness status');

Artisan::command('vogueai:core-feature-check', function (CoreFeatureReadinessService $readiness) {
    $summary = $readiness->summary();

    $this->line('VogueAI core feature readiness check');
    $this->line('=====================================');

    foreach ($summary['features'] as $feature) {
        $this->line(sprintf('[%s] %s - %s', strtoupper($feature['status']), $feature['name'], $feature['message']));

        foreach ($feature['signals'] as $signal) {
            $required = $signal['required'] ? 'required' : 'optional';
            $this->line(sprintf('      - [%s] %s (%s)', strtoupper($signal['status']), $signal['name'], $required));
        }
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    $this->line('');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check VogueAI core feature formalization for closet, search, stylist, try-on, video, and digital twin');

Artisan::command('vogueai:provider-runtime-smoke {--connect-qdrant : Attempt live Qdrant daemon and collection verification}', function (ProviderRuntimeSmokeService $runtimeSmoke) {
    $summary = $runtimeSmoke->summary((bool) $this->option('connect-qdrant'));

    $this->line('VogueAI provider runtime smoke check');
    $this->line('====================================');

    foreach ($summary['checks'] as $check) {
        $this->line(sprintf('[%s] %s - %s', strtoupper($check['status']), $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    $this->line('');
    $this->line('Secrets: API key values are intentionally never printed.');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Smoke test AI Service runtime providers: CLIP, BLIP, and Qdrant');

Artisan::command('vogueai:external-provider-smoke {--only= : Limit smoke check to tryon, video, or digital-twin}', function (ExternalProviderSmokeService $externalSmoke) {
    $only = $this->option('only');
    $only = is_string($only) && trim($only) !== '' ? trim($only) : null;
    $summary = $externalSmoke->summary($only);

    $this->line('VogueAI external provider smoke check');
    $this->line('=====================================');

    foreach ($summary['checks'] as $check) {
        $this->line(sprintf('[%s] %s - %s', strtoupper($check['status']), $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    $this->line('');
    $this->line('Secrets: API key values are intentionally never printed.');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Smoke test external providers: Try-on, Runway/Veo, and Digital Twin avatar');

Artisan::command('vogueai:tryon-status {providerTaskId : External try-on provider task id} {--request-id= : Optional local request id}', function (ExternalModelProviderService $externalModelProvider) {
    $providerTaskId = (string) $this->argument('providerTaskId');
    $requestId = $this->option('request-id');
    $requestId = is_string($requestId) && trim($requestId) !== '' ? trim($requestId) : null;

    $result = $externalModelProvider->pollTryOn($providerTaskId, $requestId);

    $this->line('VogueAI try-on provider status');
    $this->line('==============================');
    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    if (is_string($result['output_url'] ?? null) && $result['output_url'] !== '') {
        $this->line('Output URL: ' . $result['output_url']);
    }
    $this->line('');
    $this->line('Secrets: API key values are intentionally never printed.');

    return in_array($result['status'] ?? null, ['processing', 'success'], true)
        ? Command::SUCCESS
        : Command::FAILURE;
})->purpose('Poll a try-on provider task once without automatic repeated calls');

Artisan::command('vogueai:real-mode-check', function (RealModeAcceptanceService $acceptance) {
    $summary = $acceptance->summary();

    $this->line('VogueAI real mode acceptance check');
    $this->line('==================================');

    foreach ($summary['checks'] as $check) {
        $status = strtoupper($check['status']);
        $this->line(sprintf('[%s] %s - %s', $status, $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check AI Search and AI Stylist real-mode acceptance readiness');

Artisan::command('vogueai:manual-acceptance', function (ManualAcceptanceService $acceptance) {
    $summary = $acceptance->summary();

    $this->line('VogueAI manual acceptance gate');
    $this->line('==============================');

    foreach ($summary['checks'] as $check) {
        $status = strtoupper($check['status']);
        $this->line(sprintf('[%s] %s - %s', $status, $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    $this->line('');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check whether the local app is ready for manual browser acceptance QA');

Artisan::command('vogueai:production-check', function (ProductionReadinessService $readiness) {
    $summary = $readiness->summary();

    $this->line('VogueAI production readiness check');
    $this->line('==================================');

    foreach ($summary['checks'] as $check) {
        $status = strtoupper($check['status']);
        $this->line(sprintf('[%s] %s - %s', $status, $check['name'], $check['message']));
    }

    $this->line('');
    $this->line(sprintf(
        'Summary: %d failed, %d warnings',
        $summary['failed'],
        $summary['warnings']
    ));

    $this->line('');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return $summary['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Check production launch basics: env, secrets, model artifacts, policies, and UX fallbacks');

Artisan::command('vogueai:upload-scope', function (UploadScopeReviewService $review) {
    $summary = $review->summary();

    $this->line('VogueAI upload scope review');
    $this->line('===========================');
    $this->line(sprintf('Total changed/untracked entries: %d', $summary['total']));

    $this->line('');
    $this->line('Groups:');
    foreach ($summary['groups'] as $name => $count) {
        $this->line(sprintf('- %s: %d', $name, $count));
    }

    $this->line('');
    $this->line('Suggested commit groups:');
    foreach ($summary['commit_groups'] as $name => $count) {
        $this->line(sprintf('- %s: %d', $name, $count));
    }

    $this->line('');
    $this->line('Suggested commit group file lists:');
    foreach ($summary['commit_group_entries'] as $name => $entries) {
        if (empty($entries)) {
            continue;
        }

        $this->line(sprintf('- %s (%d)', $name, count($entries)));

        foreach ($entries as $entry) {
            $this->line(sprintf('  [%s] %s', $entry['status'], $entry['path']));
        }
    }

    $this->line('');
    $this->line('Confirmation items:');
    if (empty($summary['confirmation_items'])) {
        $this->line('- None');
    } else {
        foreach ($summary['confirmation_items'] as $item) {
            $this->line('- ' . $item);
        }
    }

    $this->line('');
    $this->line('Risks:');
    if (empty($summary['risks'])) {
        $this->line('- No .env or large model artifact risks detected.');
    } else {
        foreach ($summary['risks'] as $risk) {
            $this->line('- ' . $risk);
        }
    }

    $this->line('');
    $this->line('GitHub action state: review only; no staging, commit, push, or PR performed.');

    return Command::SUCCESS;
})->purpose('Review and group changed files before any GitHub staging or upload');

Artisan::command('vogueai:gemini-smoke', function (StylistTextGenerationService $stylistTextGeneration) {
    $this->line('VogueAI Gemini smoke check');
    $this->line('==========================');

    $result = $stylistTextGeneration->generate([
        'mock_mode' => false,
        'context' => [
            'occasion' => 'local Gemini smoke test',
            'weather' => 'comfortable indoor weather',
            'season_context' => 'spring',
            'formality_level' => 'smart casual',
            'mood_context' => 'confident',
            'style_preference' => 'clean minimal outfit',
            'avoid_notes' => 'do not invent clothing',
            'provider_mode' => 'real',
        ],
        'selected_items' => [
            [
                'id' => 1,
                'name' => 'White Shirt',
                'category' => 'top',
                'color' => 'white',
                'style_tags' => ['minimal', 'clean'],
            ],
            [
                'id' => 2,
                'name' => 'Navy Blazer',
                'category' => 'outerwear',
                'color' => 'navy',
                'style_tags' => ['polished', 'classic'],
            ],
        ],
        'digital_twin_profile' => [
            'dominant_category' => 'top',
            'dominant_color' => 'white',
            'dominant_style' => 'minimal',
        ],
        'embedding_signals' => [
            'top_matches' => [
                [
                    'name' => 'White Shirt',
                    'score' => 0.92,
                ],
            ],
        ],
    ]);

    $textGeneration = $result['text_generation'] ?? [];
    $status = (string) ($textGeneration['status'] ?? 'unknown');
    $mode = (string) ($textGeneration['mode'] ?? 'unknown');
    $model = (string) ($textGeneration['model'] ?? config('ai.gemini_text_model', 'unknown'));
    $fallbackActive = (bool) ($textGeneration['fallback_active'] ?? true);

    $this->line('Status: ' . $status);
    $this->line('Mode: ' . $mode);
    $this->line('Model: ' . $model);
    $this->line('Fallback active: ' . ($fallbackActive ? 'yes' : 'no'));

    if (($textGeneration['endpoint'] ?? null) !== null) {
        $this->line('Endpoint: ' . $textGeneration['endpoint']);
    }

    if (($textGeneration['error_code'] ?? null) !== null) {
        $this->line('Error code: ' . $textGeneration['error_code']);
    }

    if (($textGeneration['error_message'] ?? null) !== null) {
        $this->line('Error message: ' . $textGeneration['error_message']);
    }

    $isReady = $status === 'ready'
        && $mode === 'real_adapter'
        && $fallbackActive === false;

    $this->line('');
    $this->line($isReady
        ? 'Summary: Gemini API smoke passed.'
        : 'Summary: Gemini API smoke did not reach ready / real_adapter.');

    return $isReady ? Command::SUCCESS : Command::FAILURE;
})->purpose('Run a real Gemini text generation smoke test without printing the API key');
