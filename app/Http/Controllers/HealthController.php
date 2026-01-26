<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database check
        $dbCheck = $this->checkDatabase();
        $checks['db'] = $dbCheck;
        if (! $dbCheck['ok']) {
            $healthy = false;
        }

        // Cache check
        $cacheCheck = $this->checkCache();
        $checks['cache'] = $cacheCheck;
        if (! $cacheCheck['ok']) {
            $healthy = false;
        }

        // Storage check
        $storageCheck = $this->checkStorage();
        $checks['storage'] = $storageCheck;
        if (! $storageCheck['ok']) {
            $healthy = false;
        }

        // Queue check (optional - doesn't fail health)
        $queueCheck = $this->checkQueue();
        $checks['queue'] = $queueCheck;

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'version' => config('app.version', '1.0.0'),
            ],
            ...$checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        $ok = false;
        $latencyMs = null;
        $error = null;

        try {
            $t0 = microtime(true);
            DB::select('SELECT 1');
            $latencyMs = (int) ((microtime(true) - $t0) * 1000);
            $ok = true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'ok' => $ok,
            'latency_ms' => $latencyMs,
            'driver' => config('database.default'),
            'error' => $error,
        ];
    }

    private function checkCache(): array
    {
        $ok = false;
        $error = null;

        try {
            $testKey = 'health_check_'.uniqid();
            Cache::put($testKey, 'ok', 10);
            $ok = Cache::get($testKey) === 'ok';
            Cache::forget($testKey);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'ok' => $ok,
            'driver' => config('cache.default'),
            'error' => $error,
        ];
    }

    private function checkStorage(): array
    {
        $ok = false;
        $error = null;

        try {
            $testFile = storage_path('app/.health_check');
            file_put_contents($testFile, 'ok');
            $ok = file_exists($testFile) && file_get_contents($testFile) === 'ok';
            @unlink($testFile);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'ok' => $ok,
            'path' => storage_path(),
            'writable' => is_writable(storage_path()),
            'error' => $error,
        ];
    }

    private function checkQueue(): array
    {
        $ok = false;
        $error = null;

        try {
            // Just check if queue connection is configured
            $driver = config('queue.default');
            $ok = ! empty($driver);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'ok' => $ok,
            'driver' => config('queue.default'),
            'error' => $error,
        ];
    }
}
