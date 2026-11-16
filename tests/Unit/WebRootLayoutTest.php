<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Web root layout: only the public pages and stylesheet live under public/.
 *
 * Validates: Requirements 13
 */
final class WebRootLayoutTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testPublicContainsExactlyTheExpectedFiles(): void
    {
        $entries = array_values(array_diff(scandir($this->root . '/public') ?: [], ['.', '..']));
        sort($entries);
        $this->assertSame(['dayOffRequest.php', 'index.php', 'manageDays.php', 'styles.css'], $entries);
        foreach ($entries as $e) {
            $this->assertTrue(is_file($this->root . '/public/' . $e), "$e is not a regular file");
        }
    }

    public function testDatabaseLivesOutsidePublic(): void
    {
        $this->assertFileExists($this->root . '/data/Db3.db');
    }

    public function testNoSensitiveFilesUnderPublic(): void
    {
        $forbidden = ['db3.db', 'db.php', 'validation.php', 'nav.php', 'migrate.php',
            'composer.json', 'composer.lock', 'phpunit.xml'];
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root . '/public', FilesystemIterator::SKIP_DOTS)
        );
        $found = [];
        foreach ($it as $f) {
            if (in_array(strtolower($f->getFilename()), $forbidden, true)) {
                $found[] = $f->getPathname();
            }
        }
        $this->assertSame([], $found, 'Forbidden files under public/: ' . implode(', ', $found));
    }

    public function testDefaultDbPathResolvesToDataDirectory(): void
    {
        $expected = realpath($this->root . '/data/Db3.db');
        $actual = realpath(DEFAULT_DB_PATH);
        $this->assertNotFalse($expected);
        $this->assertSame(
            strtolower(str_replace('\\', '/', $expected)),
            strtolower(str_replace('\\', '/', (string) $actual))
        );
    }
}
