<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * Resolves a Python interpreter that actually has the `pyzk` package, instead of
 * trusting a single configured string. On Windows the web server often resolves a
 * bare "python" to a stub without pyzk (and dotenv immutability can keep a stale
 * value in a long-running `php artisan serve` process), so we probe candidates —
 * the configured path first — and pick the first one that can `import zk`.
 */
class PythonInterpreter
{
    private static ?string $resolved = null;

    public static function resolve(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $candidates = array_values(array_unique(array_filter([
            (string) config('services.zkteco.python'),
            (string) env('ZKTECO_PYTHON'),
            'C:\\Python314\\python.exe',
            'python3',
            'python',
        ], static fn ($v) => is_string($v) && $v !== '')));

        foreach ($candidates as $candidate) {
            if (self::hasZk($candidate)) {
                return self::$resolved = $candidate;
            }
        }

        // Nothing worked — return the configured value so the failure is explicit.
        return self::$resolved = ((string) config('services.zkteco.python')) ?: 'python';
    }

    /** Reset the memoized interpreter (tests / after config changes). */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    /**
     * Environment for the Python subprocess. On Windows a subprocess spawned by
     * the web server can lose SystemRoot, which breaks Winsock initialization
     * (WinError 10106) so the device never connects. Pass the full parent env and
     * guarantee SystemRoot is present.
     *
     * @return array<string, string>
     */
    public static function processEnv(): array
    {
        $env = getenv();
        if (! is_array($env)) {
            $env = [];
        }

        if (empty($env['SystemRoot']) && empty($env['SYSTEMROOT'])) {
            $env['SystemRoot'] = 'C:\\Windows';
        }

        return $env;
    }

    private static function hasZk(string $python): bool
    {
        // Probe the same way the scripts import pyzk: from the project vendor
        // folder, so any interpreter that can run at all passes.
        $vendor = base_path('scripts/vendor');
        $probe = 'import sys; sys.path.insert(0, r"'.$vendor.'"); import zk';

        $process = new Process([$python, '-c', $probe]);
        $process->setTimeout(12);

        try {
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }
}
