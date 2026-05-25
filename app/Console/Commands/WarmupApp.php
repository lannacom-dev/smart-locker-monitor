<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

/**
 * Warm-up command — run this after starting the server.
 *
 * Populates the shared file-based caches (Spatie permissions, compiled views)
 * so the first real user request is fast. OPcache for the HTTP server process
 * is warmed automatically on the first HTTP request, and persists to disk
 * via opcache.file_cache so subsequent restarts are fast too.
 *
 * Usage:
 *   php artisan app:warmup
 */
class WarmupApp extends Command
{
    protected $signature   = 'app:warmup';
    protected $description = 'Pre-warm permission cache and views so the first user request is fast.';

    public function handle(Kernel $kernel): int
    {
        $start = microtime(true);
        $this->info('Warming up application caches...');

        foreach (['/login', '/admin/login'] as $uri) {
            $this->line("  Simulating {$uri} ...");
            $t        = microtime(true);
            $request  = Request::create($uri, 'GET');
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);
            $ms       = round((microtime(true) - $t) * 1000);
            $status   = $response->getStatusCode();
            $this->line("  → HTTP {$status} in {$ms}ms");
        }

        $total = round(microtime(true) - $start);
        $this->newLine();
        $this->info("Warm-up complete in {$total}s. The application is ready.");

        return self::SUCCESS;
    }
}
