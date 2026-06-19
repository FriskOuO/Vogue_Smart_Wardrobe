<?php

namespace App\Services;

class UploadScopeReviewService
{
    private const TELESCOPE_MIGRATION = 'database/migrations/2026_04_22_161722_create_telescope_entries_table.php';

    /**
     * @param array<int, string>|null $statusLines
     * @return array<string, mixed>
     */
    public function summary(?array $statusLines = null): array
    {
        $statusLines ??= $this->gitStatusLines();
        $entries = $this->statusEntries($statusLines);
        $groups = $this->emptyGroups();
        $commitGroups = $this->emptyCommitGroups();
        $groupEntries = $this->emptyEntryGroups(array_keys($groups));
        $commitGroupEntries = $this->emptyEntryGroups(array_keys($commitGroups));

        foreach ($entries as $entry) {
            $group = $this->groupForPath($entry['path']);
            $commitGroup = $this->commitGroupForPath($entry['path'], $entry['xy']);
            $reviewEntry = [
                ...$entry,
                'status' => $this->statusLabel($entry['xy']),
                'group' => $group,
                'commit_group' => $commitGroup,
            ];

            $groups[$group]++;
            $commitGroups[$commitGroup]++;
            $groupEntries[$group][] = $reviewEntry;
            $commitGroupEntries[$commitGroup][] = $reviewEntry;
        }

        $warnings = collect($statusLines)
            ->filter(fn (string $line) => str_starts_with(strtolower($line), 'warning:'))
            ->values()
            ->all();

        $risks = $this->risks($entries);
        $confirmationItems = $this->confirmationItems($entries);

        return [
            'total' => count($entries),
            'groups' => $groups,
            'group_entries' => $groupEntries,
            'commit_groups' => $commitGroups,
            'commit_group_entries' => $commitGroupEntries,
            'warnings' => $warnings,
            'risks' => $risks,
            'confirmation_items' => $confirmationItems,
            'ready_for_upload' => count($risks) === 0 && count($confirmationItems) === 0 && count($entries) === 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyGroups(): array
    {
        return [
            'AI service' => 0,
            'Laravel backend' => 0,
            'Views and UI' => 0,
            'Tests' => 0,
            'Docs' => 0,
            'Config and scripts' => 0,
            'Database migrations' => 0,
            'Assets' => 0,
            'Other' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyCommitGroups(): array
    {
        return [
            'ai-service-adapter-contracts' => 0,
            'laravel-closet-stylist-workflows' => 0,
            'demo-readiness-provider-gates' => 0,
            'localized-ui-and-manual-polish' => 0,
            'project-docs-and-roadmap' => 0,
            'telescope-duplicate-migration-cleanup' => 0,
            'needs-manual-review' => 0,
        ];
    }

    /**
     * @param array<int, string> $names
     * @return array<string, array<int, array<string, string>>>
     */
    private function emptyEntryGroups(array $names): array
    {
        return collect($names)
            ->mapWithKeys(fn (string $name) => [$name => []])
            ->all();
    }

    private function groupForPath(string $path): string
    {
        if (str_starts_with($path, 'ai_service/')) {
            return 'AI service';
        }

        if (str_starts_with($path, 'app/')
            || str_starts_with($path, 'config/')
            || in_array($path, ['routes/web.php', 'routes/console.php'], true)) {
            return 'Laravel backend';
        }

        if (str_starts_with($path, 'resources/') || str_starts_with($path, 'lang/')) {
            return 'Views and UI';
        }

        if (str_starts_with($path, 'tests/')) {
            return 'Tests';
        }

        if (str_starts_with($path, 'docs/') || $path === 'README.md') {
            return 'Docs';
        }

        if (str_starts_with($path, 'database/migrations/')) {
            return 'Database migrations';
        }

        if (str_starts_with($path, 'public/images/')) {
            return 'Assets';
        }

        if (in_array($path, ['.env.example', '.gitignore', 'start-all.ps1', 'start-qdrant.ps1'], true)) {
            return 'Config and scripts';
        }

        return 'Other';
    }

    private function commitGroupForPath(string $path, string $xy): string
    {
        if ($path === self::TELESCOPE_MIGRATION && str_contains($xy, 'D')) {
            return 'telescope-duplicate-migration-cleanup';
        }

        if (str_starts_with($path, 'ai_service/')) {
            return 'ai-service-adapter-contracts';
        }

        if (str_starts_with($path, 'resources/')
            || str_starts_with($path, 'lang/')
            || str_starts_with($path, 'tests/Feature/Auth/')
            || in_array($path, ['tests/Feature/ExampleTest.php', 'tests/Feature/ProfileTest.php'], true)) {
            return 'localized-ui-and-manual-polish';
        }

        if (str_starts_with($path, 'docs/') || $path === 'README.md') {
            return 'project-docs-and-roadmap';
        }

        if (str_starts_with($path, 'app/Services/CoreFeature')
            || str_starts_with($path, 'app/Services/Demo')
            || str_starts_with($path, 'app/Services/ExternalModel')
            || str_starts_with($path, 'app/Services/Gemini')
            || str_starts_with($path, 'app/Services/Github')
            || str_starts_with($path, 'app/Services/ModelProvider')
            || str_starts_with($path, 'app/Services/Provider')
            || str_starts_with($path, 'app/Services/Production')
            || str_starts_with($path, 'app/Services/RealMode')
            || str_starts_with($path, 'app/Services/UploadScope')
            || str_starts_with($path, 'tests/Feature/CoreFeature')
            || str_starts_with($path, 'tests/Feature/Demo')
            || str_starts_with($path, 'tests/Feature/ExternalModel')
            || str_starts_with($path, 'tests/Feature/ExternalProvider')
            || str_starts_with($path, 'tests/Feature/Gemini')
            || str_starts_with($path, 'tests/Feature/Github')
            || str_starts_with($path, 'tests/Feature/ManualAcceptance')
            || str_starts_with($path, 'tests/Feature/ModelProvider')
            || str_starts_with($path, 'tests/Feature/Provider')
            || str_starts_with($path, 'tests/Feature/Production')
            || str_starts_with($path, 'tests/Feature/RealMode')
            || str_starts_with($path, 'tests/Feature/UploadScope')
            || str_starts_with($path, 'public/images/demo/')
            || in_array($path, ['.env.example', '.gitignore', 'routes/console.php', 'start-all.ps1', 'start-qdrant.ps1'], true)) {
            return 'demo-readiness-provider-gates';
        }

        if (str_starts_with($path, 'app/')
            || str_starts_with($path, 'config/')
            || str_starts_with($path, 'database/migrations/')
            || $path === 'routes/web.php'
            || str_starts_with($path, 'tests/Feature/Ai')
            || str_starts_with($path, 'tests/Feature/SmartCloset')) {
            return 'laravel-closet-stylist-workflows';
        }

        return 'needs-manual-review';
    }

    /**
     * @param array<int, array{line: string, xy: string, path: string}> $entries
     * @return array<int, string>
     */
    private function risks(array $entries): array
    {
        $risks = [];

        if (collect($entries)->contains(fn (array $entry) => $entry['path'] === '.env')) {
            $risks[] = '.env appears in git status; do not upload local secrets.';
        }

        $largeModelEntry = collect($entries)->first(
            fn (array $entry) => preg_match('/\.(safetensors|bin|pt|pth|onnx|gguf)$/i', $entry['path']) === 1
        );

        if ($largeModelEntry) {
            $risks[] = 'Large model artifact appears in git status: ' . $largeModelEntry['path'];
        }

        return $risks;
    }

    /**
     * @param array<int, array{line: string, xy: string, path: string}> $entries
     * @return array<int, string>
     */
    private function confirmationItems(array $entries): array
    {
        $items = [];

        if (count($entries) > 0) {
            $items[] = sprintf('Confirm all %d changed/untracked entries are intended for upload.', count($entries));
        }

        $hasTelescopeDeletion = collect($entries)->contains(
            fn (array $entry) => $entry['path'] === self::TELESCOPE_MIGRATION && str_contains($entry['xy'], 'D')
        );

        if ($hasTelescopeDeletion) {
            $items[] = 'Confirm Telescope cleanup: keep 2026_04_22_161640 and delete 2026_04_22_161722.';
        }

        return $items;
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
            return ['!! git status failed: unable to start git status'];
        }

        $output = stream_get_contents($pipes[1]) ?: '';
        $errorOutput = stream_get_contents($pipes[2]) ?: '';

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return collect([$output, $errorOutput])
            ->flatMap(fn (string $text) => preg_split('/\R/', $text) ?: [])
            ->map(fn (string $line) => rtrim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $statusLines
     * @return array<int, array{line: string, xy: string, path: string}>
     */
    private function statusEntries(array $statusLines): array
    {
        return collect($statusLines)
            ->filter(fn (string $line) => preg_match('/^[ MADRCU?!]{2}\s+.+$/', $line) === 1)
            ->map(function (string $line) {
                return [
                    'line' => $line,
                    'xy' => substr($line, 0, 2),
                    'path' => $this->statusPath($line),
                ];
            })
            ->values()
            ->all();
    }

    private function statusLabel(string $xy): string
    {
        if ($xy === '??') {
            return 'untracked';
        }

        if (str_contains($xy, 'D')) {
            return 'deleted';
        }

        if (str_contains($xy, 'A')) {
            return 'added';
        }

        if (str_contains($xy, 'R')) {
            return 'renamed';
        }

        if (str_contains($xy, 'M')) {
            return 'modified';
        }

        return trim($xy) !== '' ? trim($xy) : 'changed';
    }

    private function statusPath(string $line): string
    {
        $path = trim(substr($line, 3));

        if (str_contains($path, ' -> ')) {
            return trim((string) str($path)->afterLast(' -> '));
        }

        return trim($path, '"');
    }
}
