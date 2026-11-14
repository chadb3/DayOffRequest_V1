<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Data access isolation: only db.php may contain engine-specific code.
 *
 * Validates: Requirements 12.1, 12.2
 */
final class DataAccessIsolationTest extends TestCase
{
    public function testOnlyDbPhpContainsEngineSpecificCode(): void
    {
        $root = dirname(__DIR__, 2);
        $files = glob($root . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $files = array_values(array_filter(
            $files,
            static fn (string $f): bool => strtolower(basename($f)) !== 'db.php'
        ));

        $this->assertNotEmpty($files, 'No application .php files were scanned.');

        $patterns = ['SQLite3', 'SQLITE3_', 'PRAGMA', 'datetime('];
        $offences = [];
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertNotFalse($contents, "Could not read $file");
            foreach ($patterns as $pattern) {
                if (stripos($contents, $pattern) !== false) {
                    $offences[] = basename($file) . " contains '$pattern'";
                }
            }
        }

        $this->assertSame([], $offences, "Engine-specific code outside db.php: " . implode('; ', $offences));
    }
}
