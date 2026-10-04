<?php

namespace App\Support;

use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class Deployer
{
    public function snapshot(bool $fetch = false): array
    {
        $remote = (string) config('deploy.remote');
        $branch = (string) config('deploy.branch');
        $payload = [
            'site' => config('app.url'),
            'branch' => $branch,
            'repository' => null,
            'commit' => null,
            'dirty' => [],
            'ahead' => null,
            'pending' => null,
            'checked' => $fetch,
            'error' => null,
            'binaries' => [
                'git' => $this->resolveBinary('git') !== null,
                'composer' => $this->resolveBinary('composer') !== null,
                'php' => PHP_VERSION,
            ],
            'job' => $this->readJob(),
        ];

        if ($payload['binaries']['git'] === false || ! $this->insideRepository()) {
            $payload['error'] = 'Git tidak ditemukan, atau folder aplikasi ini bukan repositori Git.';

            return $payload;
        }

        $payload['repository'] = $this->repositoryLabel();
        $payload['commit'] = $this->currentCommit();
        $payload['dirty'] = $this->dirtyFiles();

        if (! $fetch) {
            return $payload;
        }

        $fetched = $this->git(['fetch', $remote, $branch], 90);
        if (! $fetched->successful()) {
            $payload['error'] = $this->clip($this->combined($fetched)) ?: 'Gagal menghubungi GitHub.';

            return $payload;
        }

        $remoteRef = $remote.'/'.$branch;
        $ahead = $this->count($remoteRef.'..HEAD');
        $behind = $this->count('HEAD..'.$remoteRef);
        if ($ahead === null || $behind === null) {
            $payload['error'] = 'Tidak dapat membandingkan komit dengan GitHub.';

            return $payload;
        }

        $payload['ahead'] = $ahead;
        $payload['pending'] = [
            'count' => $behind,
            'commits' => $behind > 0 ? $this->commits('HEAD..'.$remoteRef) : [],
        ];

        return $payload;
    }

    public function dispatch(bool $rebuild): array
    {
        $job = $this->readJob();
        if (in_array($job['state'], ['queued', 'running'], true) && $this->fresh($job)) {
            throw ValidationException::withMessages([
                'update' => 'Pembaruan masih berjalan.',
            ]);
        }

        if (app()->runningUnitTests()) {
            return $this->writeJob([
                'state' => 'success',
                'mode' => $rebuild ? 'rebuild' : 'update',
                'message' => 'Pembaruan tidak dijalankan saat pengujian.',
                'started_at' => now()->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'steps' => [],
            ]);
        }

        $this->writeJob([
            'state' => 'queued',
            'mode' => $rebuild ? 'rebuild' : 'update',
            'message' => 'Pembaruan dijadwalkan.',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'steps' => [],
        ]);
        $this->spawn($rebuild);

        return $this->readJob();
    }

    public function run(bool $rebuild): bool
    {
        set_time_limit(0);
        $this->begin($rebuild);
        $maintenance = false;

        try {
            $missing = $this->missingBinaries($rebuild);
            if ($missing !== []) {
                throw new RuntimeException('Program belum tersedia untuk PHP di server: '.implode(', ', $missing).'.');
            }

            if (! $rebuild) {
                $remote = (string) config('deploy.remote');
                $branch = (string) config('deploy.branch');
                $this->attempt('fetch', function () use ($remote, $branch) {
                    return $this->must($this->git(['fetch', $remote, $branch], 90));
                });

                $remoteRef = $remote.'/'.$branch;
                $ahead = $this->count($remoteRef.'..HEAD');
                $behind = $this->count('HEAD..'.$remoteRef);
                if ($ahead === null || $behind === null) {
                    throw new RuntimeException('Tidak dapat membandingkan komit dengan GitHub.');
                }

                if ($ahead > 0) {
                    throw new RuntimeException('Server punya komit yang belum ada di GitHub. Pembaruan dihentikan supaya pekerjaan di server tidak tertimpa.');
                }

                if ($behind === 0) {
                    $this->skipRemaining('Sudah versi terbaru. Tidak ada yang dipasang.');

                    return true;
                }
            } else {
                $this->mark('fetch', 'skipped');
            }

            $dirty = $this->dirtyFiles();
            if ($dirty !== []) {
                throw new RuntimeException('Ada perubahan lokal pada file yang dilacak Git: '.implode(', ', $dirty).'.');
            }

            $this->attempt('down', function () use (&$maintenance) {
                $output = $this->must($this->artisan(['down', '--retry=15', '--refresh=15']));
                $maintenance = true;

                return $output;
            });

            foreach ($this->pipeline($rebuild) as $step) {
                $this->attempt($step['key'], function () use ($step) {
                    return $this->must(
                        $this->command($step['command'], $step['timeout']),
                        (bool) ($step['allow_existing'] ?? false),
                    );
                });
            }

            $this->finish('Pembaruan selesai dipasang. Muat ulang halaman CV.');

            return true;
        } catch (Throwable $exception) {
            $this->fail($exception->getMessage());

            return false;
        } finally {
            if ($maintenance) {
                try {
                    $output = $this->must($this->artisan(['up']));
                    $this->mark('up', 'ok', $output);
                } catch (Throwable $exception) {
                    $this->mark('up', 'failed', $exception->getMessage());
                }
            }
        }
    }

    /**
     * @return array<int, array{key: string, label: string, command: array<int, string>, timeout: int, allow_existing?: bool}>
     */
    public function pipeline(bool $rebuild): array
    {
        $steps = [];
        $remote = (string) config('deploy.remote');
        $branch = (string) config('deploy.branch');

        if (! $rebuild) {
            $steps[] = [
                'key' => 'pull',
                'label' => 'Tarik kode',
                'command' => $this->gitCommand(['pull', '--ff-only', $remote, $branch]),
                'timeout' => 120,
            ];
        }

        $composer = [$this->resolveBinary('composer') ?? 'composer', 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'];
        if (config('deploy.no_dev')) {
            $composer[] = '--no-dev';
        }

        return [
            ...$steps,
            [
                'key' => 'composer',
                'label' => 'Pasang dependensi PHP',
                'command' => $composer,
                'timeout' => 600,
            ],
            [
                'key' => 'migrate',
                'label' => 'Migrasi database',
                'command' => [PHP_BINARY, base_path('artisan'), 'migrate', '--force'],
                'timeout' => 180,
            ],
            [
                'key' => 'storage',
                'label' => 'Tautan berkas',
                'command' => [PHP_BINARY, base_path('artisan'), 'storage:link'],
                'timeout' => 60,
                'allow_existing' => true,
            ],
            [
                'key' => 'optimize',
                'label' => 'Optimasi',
                'command' => [PHP_BINARY, base_path('artisan'), 'optimize'],
                'timeout' => 120,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function missingBinaries(bool $rebuild): array
    {
        $required = $rebuild ? ['composer'] : ['git', 'composer'];
        $missing = [];

        foreach ($required as $name) {
            if ($this->resolveBinary($name) === null) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    private function begin(bool $rebuild): void
    {
        $steps = [
            ['key' => 'fetch', 'label' => 'Cek GitHub', 'status' => 'pending', 'output' => ''],
            ['key' => 'down', 'label' => 'Mode pemeliharaan', 'status' => 'pending', 'output' => ''],
        ];

        foreach ($this->pipeline($rebuild) as $step) {
            $steps[] = [
                'key' => $step['key'],
                'label' => $step['label'],
                'status' => 'pending',
                'output' => '',
            ];
        }

        $steps[] = ['key' => 'up', 'label' => 'Aktifkan situs', 'status' => 'pending', 'output' => ''];

        $this->writeJob([
            'state' => 'running',
            'mode' => $rebuild ? 'rebuild' : 'update',
            'message' => 'Pembaruan sedang berjalan. Jangan tutup halaman ini.',
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'steps' => $steps,
        ]);
    }

    private function attempt(string $key, callable $callback): void
    {
        $this->mark($key, 'running');
        $output = $callback();
        $this->mark($key, 'ok', is_string($output) ? $output : '');
    }

    private function finish(string $message): void
    {
        $job = $this->readJob();
        $job['state'] = 'success';
        $job['message'] = $message;
        $job['finished_at'] = now()->toIso8601String();
        $this->writeJob($job);
    }

    private function fail(string $message): void
    {
        $job = $this->readJob();
        $job['state'] = 'failed';
        $job['message'] = $this->clip($message);
        $job['finished_at'] = now()->toIso8601String();

        foreach ($job['steps'] as &$step) {
            if ($step['status'] === 'running') {
                $step['status'] = 'failed';
                $step['output'] = $job['message'];
            } elseif ($step['status'] === 'pending') {
                $step['status'] = 'skipped';
            }
        }

        $this->writeJob($job);
    }

    private function skipRemaining(string $message): void
    {
        $job = $this->readJob();

        foreach ($job['steps'] as &$step) {
            if ($step['status'] === 'pending') {
                $step['status'] = 'skipped';
            }
        }

        $job['state'] = 'success';
        $job['message'] = $message;
        $job['finished_at'] = now()->toIso8601String();
        $this->writeJob($job);
    }

    private function mark(string $key, string $status, string $output = ''): void
    {
        $job = $this->readJob();

        foreach ($job['steps'] as &$step) {
            if ($step['key'] === $key) {
                $step['status'] = $status;
                if ($output !== '') {
                    $step['output'] = $this->clip($output);
                }
            }
        }

        $this->writeJob($job);
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function fresh(array $job): bool
    {
        $started = strtotime((string) ($job['started_at'] ?? ''));

        return $started !== false && (time() - $started) < 1200;
    }

    private function spawn(bool $rebuild): void
    {
        $php = escapeshellarg(PHP_BINARY);
        $artisan = escapeshellarg(base_path('artisan'));
        $flag = $rebuild ? ' --rebuild' : '';

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen('start /B "" '.$php.' '.$artisan.' app:update'.$flag, 'r'));

            return;
        }

        exec('nohup '.$php.' '.$artisan.' app:update'.$flag.' > /dev/null 2>&1 &');
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function git(array $arguments, int $timeout = 30): ProcessResult
    {
        return $this->command($this->gitCommand($arguments), $timeout);
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function gitCommand(array $arguments): array
    {
        return array_merge([
            $this->resolveBinary('git') ?? 'git',
            '-c',
            'safe.directory='.$this->directory(),
        ], $arguments);
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function artisan(array $arguments): ProcessResult
    {
        return $this->command(array_merge([PHP_BINARY, base_path('artisan')], $arguments), 60);
    }

    /**
     * @param  array<int, string>  $command
     */
    private function command(array $command, int $timeout): ProcessResult
    {
        $path = $this->searchPath();
        $pending = Process::path(base_path())
            ->timeout($timeout)
            ->env([
                'GIT_TERMINAL_PROMPT' => '0',
                'PATH' => $path,
                'Path' => $path,
            ]);

        if (PHP_OS_FAMILY === 'Windows' && preg_match('/\.(cmd|bat)$/i', $command[0])) {
            array_unshift($command, 'cmd', '/D', '/C');
        }

        return $pending->run($command);
    }

    private function insideRepository(): bool
    {
        $result = $this->git(['rev-parse', '--is-inside-work-tree']);

        return $result->successful() && trim($result->output()) === 'true';
    }

    /**
     * @return array{hash: string, short: string, subject: string, date: string}|null
     */
    private function currentCommit(): ?array
    {
        $result = $this->git(['log', '-1', '--format=%H%x1f%h%x1f%s%x1f%cI']);
        if (! $result->successful()) {
            return null;
        }

        $parts = explode("\x1f", trim($result->output()));
        if (count($parts) < 4) {
            return null;
        }

        return [
            'hash' => $parts[0],
            'short' => $parts[1],
            'subject' => $parts[2],
            'date' => $parts[3],
        ];
    }

    private function repositoryLabel(): ?string
    {
        $result = $this->git(['remote', 'get-url', (string) config('deploy.remote')]);
        if (! $result->successful()) {
            return null;
        }

        $url = $this->redact(trim($result->output()));
        if (preg_match('#github\.com[:/]([^/\s:]+/[^/\s]+?)(?:\.git)?$#', $url, $matches) === 1) {
            return $matches[1];
        }

        return $url;
    }

    /**
     * @return array<int, string>
     */
    private function dirtyFiles(): array
    {
        $result = $this->git(['status', '--porcelain', '--untracked-files=no']);
        if (! $result->successful()) {
            return [];
        }

        $files = [];
        foreach (preg_split("/\r\n|\n|\r/", rtrim($result->output())) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            $files[] = trim(substr($line, 3)) ?: trim($line);
        }

        return $files;
    }

    private function count(string $range): ?int
    {
        $result = $this->git(['rev-list', '--count', $range]);

        return $result->successful() ? (int) trim($result->output()) : null;
    }

    /**
     * @return array<int, array{hash: string, subject: string}>
     */
    private function commits(string $range): array
    {
        $result = $this->git(['log', '--oneline', '-n', '20', '--format=%h%x09%s', $range]);
        if (! $result->successful()) {
            return [];
        }

        $commits = [];
        foreach (preg_split("/\r\n|\n|\r/", trim($result->output())) ?: [] as $line) {
            if ($line === '') {
                continue;
            }
            [$hash, $subject] = array_pad(explode("\t", $line, 2), 2, '');
            $commits[] = ['hash' => $hash, 'subject' => $subject];
        }

        return $commits;
    }

    private function must(ProcessResult $result, bool $allowExisting = false): string
    {
        $text = $this->clip($this->combined($result));
        if ($result->successful() || ($allowExisting && str_contains(strtolower($text), 'already exists'))) {
            return $text;
        }

        throw new RuntimeException($text !== '' ? $text : 'Perintah gagal tanpa keluaran.');
    }

    private function combined(ProcessResult $result): string
    {
        return trim($result->output()."\n".$result->errorOutput());
    }

    private function redact(string $text): string
    {
        $text = preg_replace('#://[^/\s:]+:[^@\s]+@#', '://***@', $text) ?? $text;

        return preg_replace('#\b(?:ghp|gho|github_pat)_[A-Za-z0-9_]+#', '***', $text) ?? $text;
    }

    private function clip(string $text): string
    {
        $text = trim($this->redact($text));
        if (strlen($text) <= 8000) {
            return $text;
        }

        return "…\n".substr($text, -8000);
    }

    private function directory(): string
    {
        return str_replace('\\', '/', base_path());
    }

    private function resolveBinary(string $name): ?string
    {
        if (array_key_exists($name, $this->binaries)) {
            return $this->binaries[$name];
        }

        $configured = (string) config('deploy.binaries.'.$name, $name);
        if ($configured !== $name && is_file($configured)) {
            return $this->binaries[$name] = $configured;
        }

        foreach ($this->extraDirectories() as $directory) {
            foreach ($this->executableNames($name) as $file) {
                $candidate = $directory.DIRECTORY_SEPARATOR.$file;
                if (is_file($candidate)) {
                    return $this->binaries[$name] = $candidate;
                }
            }
        }

        $candidate = $configured !== '' ? $configured : $name;
        $finder = PHP_OS_FAMILY === 'Windows'
            ? ['where.exe', $candidate]
            : ['sh', '-c', 'command -v '.escapeshellarg($candidate)];
        $result = Process::path(base_path())->env([
            'PATH' => $this->searchPath(),
            'Path' => $this->searchPath(),
        ])->run($finder);

        if (! $result->successful()) {
            return $this->binaries[$name] = null;
        }

        $path = trim(strtok($result->output(), "\r\n") ?: '');

        return $this->binaries[$name] = ($path !== '' ? $path : $candidate);
    }

    /**
     * @return array<int, string>
     */
    private function executableNames(string $name): array
    {
        return match ($name) {
            'git' => ['git.exe', 'git'],
            'composer' => ['composer.bat', 'composer.phar', 'composer'],
            default => [$name],
        };
    }

    /**
     * @return array<int, string>
     */
    private function extraDirectories(): array
    {
        $directories = [dirname(PHP_BINARY)];

        if (PHP_OS_FAMILY === 'Windows') {
            $directories = array_merge($directories, [
                'C:\\Program Files\\Git\\cmd',
                'C:\\Program Files\\nodejs',
                'D:\\Laragon\\bin\\git\\cmd',
                'D:\\Laragon\\bin\\composer',
            ]);
            foreach (glob('D:\\Laragon\\bin\\nodejs\\node-v*') ?: [] as $directory) {
                $directories[] = $directory;
            }
        } else {
            $directories = array_merge($directories, [
                '/usr/local/sbin',
                '/usr/local/bin',
                '/usr/sbin',
                '/usr/bin',
                '/bin',
                '/snap/bin',
            ]);
            $home = getenv('HOME') ?: '';
            if ($home !== '') {
                $directories[] = $home.'/.composer/vendor/bin';
                $directories[] = $home.'/.config/composer/vendor/bin';
                foreach (glob($home.'/.nvm/versions/node/*/bin') ?: [] as $directory) {
                    $directories[] = $directory;
                }
            }
        }

        return array_values(array_filter($directories, 'is_dir'));
    }

    private function searchPath(): string
    {
        $current = getenv('PATH') ?: getenv('Path') ?: '';
        $separator = PHP_OS_FAMILY === 'Windows' ? ';' : ':';

        return implode($separator, [...$this->extraDirectories(), $current]);
    }

    /**
     * @var array<string, string|null>
     */
    private array $binaries = [];

    /**
     * @return array{state: string, mode: string|null, message: string|null, started_at: string|null, finished_at: string|null, steps: array<int, array<string, string>>}
     */
    private function readJob(): array
    {
        $path = $this->statusPath();
        if (! is_file($path)) {
            return $this->emptyJob();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? array_merge($this->emptyJob(), $decoded) : $this->emptyJob();
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    private function writeJob(array $job): array
    {
        $path = $this->statusPath();
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $job;
    }

    /**
     * @return array{state: string, mode: null, message: null, started_at: null, finished_at: null, steps: array<int, mixed>}
     */
    private function emptyJob(): array
    {
        return [
            'state' => 'idle',
            'mode' => null,
            'message' => null,
            'started_at' => null,
            'finished_at' => null,
            'steps' => [],
        ];
    }

    private function statusPath(): string
    {
        return storage_path(app()->runningUnitTests() ? 'framework/testing/deploy-status.json' : 'app/deploy-status.json');
    }
}
