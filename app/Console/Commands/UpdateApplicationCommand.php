<?php

namespace App\Console\Commands;

use App\Support\Deployer;
use Illuminate\Console\Command;

class UpdateApplicationCommand extends Command
{
    protected $signature = 'app:update {--rebuild : Pasang ulang dependensi PHP tanpa git pull}';

    protected $description = 'Tarik kode dari GitHub, pasang dependensi PHP, lalu migrasi';

    public function handle(Deployer $deployer): int
    {
        $ok = $deployer->run((bool) $this->option('rebuild'));
        $path = storage_path('app/deploy-status.json');
        $job = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $message = is_array($job) ? (string) ($job['message'] ?? '') : '';

        if ($message !== '') {
            $ok ? $this->info($message) : $this->error($message);
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
