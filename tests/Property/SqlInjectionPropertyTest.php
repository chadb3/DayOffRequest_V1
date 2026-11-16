<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class SqlInjectionPropertyTest extends DbTestCase
{
    use TestTrait;

    private const FRAGMENTS = [
        "'", '"', ';', '--', '/*', '*/', '\\', '%', '_',
        "' OR '1'='1", "'; DROP TABLE EMPDAYOFFREQ; --",
        '"; DELETE FROM EMPDAYOFFREQ; --', 'DROP TABLE EMPDAYOFFREQ_ARCHIVE;',
        'DELETE FROM EMPDAYOFFREQ', "') ; UPDATE EMPDAYOFFREQ SET STATUS='Approved'; --",
        'UNION SELECT * FROM sqlite_master', ':name', '?', 'abc', ' ',
    ];

    /**
     * Feature: day-off-request, Property 2: SQL injection inputs stored literally
     *
     * **Validates: Requirements 1.2**
     */
    public function testSqlInjectionInputsStoredLiterally(): void
    {
        $injection = Generators::map(
            static fn(array $parts): string => implode('', $parts),
            Generators::tuple(
                Generators::elements(self::FRAGMENTS),
                Generators::elements(self::FRAGMENTS),
                Generators::elements(self::FRAGMENTS)
            )
        );

        $this->limitTo(100)
            ->forAll($injection, $injection, $injection, $injection, $injection)
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason): void {
                $before = (int)$this->db->querySingle('SELECT COUNT(*) FROM EMPDAYOFFREQ');

                $num = insertRequest($this->db, [
                    'name' => $name,
                    'phone' => $phone,
                    'shifts' => $shifts,
                    'sub' => $sub,
                    'reason' => $reason,
                ]);

                $row = getRequestById($this->db, $num);
                $this->assertNotNull($row);
                $this->assertSame($name, $row['NAME']);
                $this->assertSame($phone, $row['PHONE']);
                $this->assertSame($shifts, $row['SHIFTS']);
                $this->assertSame($sub, $row['SUB']);
                $this->assertSame($reason, $row['REASON']);
                $this->assertSame('Pending', $row['STATUS']);

                // Tables intact, exactly one row added, no side effects.
                $tables = [];
                $res = $this->db->query("SELECT name FROM sqlite_master WHERE type='table'");
                while ($t = $res->fetchArray(SQLITE3_ASSOC)) {
                    $tables[] = $t['name'];
                }
                $this->assertContains('EMPDAYOFFREQ', $tables);
                $this->assertContains('EMPDAYOFFREQ_ARCHIVE', $tables);
                $this->assertSame($before + 1, (int)$this->db->querySingle('SELECT COUNT(*) FROM EMPDAYOFFREQ'));
                $this->assertSame(0, (int)$this->db->querySingle("SELECT COUNT(*) FROM EMPDAYOFFREQ WHERE STATUS <> 'Pending'"));
            });
    }
}
