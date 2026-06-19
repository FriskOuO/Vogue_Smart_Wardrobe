<?php

namespace App\Services;

class GithubReadinessService
{
    private const TELESCOPE_MIGRATION = 'database/migrations/2026_04_22_161722_create_telescope_entries_table.php';

    /**
     * @param array<int, string>|null $statusLines
     * @return array{ok: bool, blockers: int, warnings: int, checks: array<int, array<string, mixed>>}
     */
    public function summary(?array $statusLines = null): array
    {
        $statusLines ??= $this->gitStatusLines();
        $statusEntries = $this->statusEntries($statusLines);

        $checks = [
            $this->checkGitAvailable($statusLines),
            $this->checkGitStatusWarnings($statusLines),
            $this->checkWorktreeReviewed($statusEntries),
            $this->checkTelescopeDeletion($statusEntries),
            $this->checkNoLocalEnvTracked($statusEntries),
            $this->checkLargeModelFiles($statusEntries),
        ];

        $blockers = collect($checks)
            ->where('status', 'block')
            ->count();
        $warnings = collect($checks)
            ->where('status', 'warn')
            ->count();

        return [
            'ok' => $blockers === 0,
            'blockers' => $blockers,
            'warnings' => $warnings,
            'checks' => $checks,
        ];
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

        $exitCode = proc_close($process);
        $lines = $this->processLines($output, $errorOutput);

        if ($exitCode !== 0) {
            return ['!! git status failed: ' . implode(' ', $lines)];
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function processLines(string ...$outputs): array
    {
        return collect($outputs)
            ->flatMap(fn (string $output) => preg_split('/\R/', $output) ?: [])
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

    private function statusPath(string $line): string
    {
        $path = trim(substr($line, 3));

        if (str_contains($path, ' -> ')) {
            return trim((string) str($path)->afterLast(' -> '));
        }

        return trim($path, '"');
    }

    /**
     * @param array<int, string> $statusLines
     * @return array{name: string, status: string, message: string}
     */
    private function checkGitAvailable(array $statusLines): array
    {
        $failed = collect($statusLines)
            ->contains(fn (string $line) => str_starts_with($line, '!! git status failed:'));

        return [
            'name' => 'Git status',
            'status' => $failed ? 'block' : 'pass',
            'message' => $failed ? 'git status failed; cannot verify repository state.' : 'git status is available.',
        ];
    }

    /**
     * @param array<int, string> $statusLines
     * @return array{name: string, status: string, message: string}
     */
    private function checkGitStatusWarnings(array $statusLines): array
    {
        $warnings = collect($statusLines)
            ->filter(fn (string $line) => str_starts_with(strtolower($line), 'warning:'))
            ->values();

        return [
            'name' => 'Git status warnings',
            'status' => $warnings->isNotEmpty() ? 'warn' : 'pass',
            'message' => $warnings->isNotEmpty()
                ? 'git status emitted warnings; review before GitHub: ' . $warnings->implode(' | ')
                : 'No git status warnings detected.',
        ];
    }

    /**
     * @param array<int, array{line: string, xy: string, path: string}> $statusEntries
     * @return array{name: string, status: string, message: string}
     */
    private function checkWorktreeReviewed(array $statusEntries): array
    {
        if (empty($statusEntries)) {
            return [
                'name' => 'Worktree review',
                'status' => 'pass',
                'message' => 'Worktree is clean.',
            ];
        }

        return [
            'name' => 'Worktree review',
            'status' => 'block',
            'message' => sprintf(
                'Worktree has %d changed/untracked entries; review, stage, and commit intentionally before GitHub.',
                count($statusEntries)
            ),
        ];
    }

    /**
     * @param array<int, array{line: string, xy: string, path: string}> $statusEntries
     * @return array{name: string, status: string, message: string}
     */
    private function checkTelescopeDeletion(array $statusEntries): array
    {
        $deleted = collect($statusEntries)
            ->contains(fn (array $entry) => $entry['path'] === self::TELESCOPE_MIGRATION
                && str_contains($entry['xy'], 'D'));

        return [
            'name' => 'Telescope migration deletion',
            'status' => $deleted ? 'block' : 'pass',
            'message' => $deleted
                ? 'Tracked duplicate Telescope migration deletion must be explicitly confirmed before GitHub.'
                : 'No tracked Telescope migration deletion detected in git status.',
        ];
    }

    /**
     * @param array<int, array{line: string, xy: string, path: string}> $statusEntries
     * @return array{name: string, status: string, message: string}
     */
    private function checkNoLocalEnvTracked(array $statusEntries): array
    {
        $envChanged = collect($statusEntries)
            ->contains(fn (array $entry) => $entry['path'] === '.env');

        return [
            'name' => 'Local .env safety',
            'status' => $envChanged ? 'block' : 'pass',
            'message' => $envChanged
                ? '.env appears in git status; never upload local secrets.'
                : 'Local .env is not listed in git status.',
        ];
    }

    /**
     * @param array<int, array{line: string, xy: string, path: string}> $statusEntries
     * @return array{name: string, status: string, message: string}
     */
    private function checkLargeModelFiles(array $statusEntries): array
    {
        $largeModelEntry = collect($statusEntries)
            ->first(function (array $entry) {
                return preg_match('/\.(safetensors|bin|pt|pth|onnx|gguf)$/i', $entry['path']) === 1;
            });

        return [
            'name' => 'Large model artifacts',
            'status' => $largeModelEntry ? 'block' : 'pass',
            'message' => $largeModelEntry
                ? 'Large model artifact appears in git status: ' . trim($largeModelEntry['line'])
                : 'No large model artifact extensions detected in git status.',
        ];
    }
}
