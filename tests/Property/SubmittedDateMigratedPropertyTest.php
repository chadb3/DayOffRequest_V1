<?php

require_once __DIR__ . '/../MigratedDbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Bug condition exploration for submitted-date-empty-fix.
 * Run on UNFIXED code: insert and archive tests are expected to fail
 * (SUBMITTED_DATE stored as ''). The default-inspection test passes.
 */
#[Group('property')]
final class SubmittedDateMigratedPropertyTest extends MigratedDbTestCase
{
    use TestTrait;

    private const REASONS = ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other'];
    private const FORMAT = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';

    /**
     * Feature: submitted-date-empty-fix, Property 1: Submitted date is set on every insert
     *
     * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
     */
    public function testInsertOnMigratedSchemaStoresCurrentSubmittedDate(): void
    {
        $text = Generators::suchThat(
            static fn(string $s): bool => trim($s) !== '',
            Generators::string()
        );

        $this->limitTo(100)
            ->forAll($text, $text, $text, $text, Generators::elements(self::REASONS))
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason): void {
                $num = insertRequest($this->db, [
                    'name' => $name, 'phone' => $phone, 'shifts' => $shifts,
                    'sub' => $sub, 'reason' => $reason,
                ]);
                $row = getRequestById($this->db, $num);
                $this->assertNotNull($row);

                $this->assertMatchesRegularExpression(self::FORMAT, $row['SUBMITTED_DATE']);
                $now = $this->db->querySingle("SELECT datetime('now','localtime')");
                $this->assertLessThanOrEqual(5, self::secondsBetween($row['SUBMITTED_DATE'], $now));
            });
    }

    /**
     * **Validates: Requirements 2.2, 2.3**
     */
    public function testArchivedMigratedRowKeepsNonEmptySubmittedDate(): void
    {
        $num = insertRequest($this->db, [
            'name' => 'Ann', 'phone' => '555', 'shifts' => 'Mon', 'sub' => 'Bob', 'reason' => 'Medical',
        ]);
        $this->assertTrue(updateRequestStatus($this->db, $num, 'Approved'));
        $this->assertTrue(archiveRequest($this->db, $num));

        $archived = getArchivedRequestById($this->db, $num);
        $this->assertNotNull($archived);
        $this->assertMatchesRegularExpression(self::FORMAT, $archived['SUBMITTED_DATE']);
    }

    /**
     * Documents the root cause: ALTER TABLE ADD COLUMN left DEFAULT ''.
     *
     * **Validates: Requirements 2.1**
     */
    public function testMigratedSubmittedDateColumnDefaultIsEmptyString(): void
    {
        $result = $this->db->query('PRAGMA table_info(EMPDAYOFFREQ)');
        $default = null;
        while ($col = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($col['name'] === 'SUBMITTED_DATE') {
                $default = $col['dflt_value'];
            }
        }
        $this->assertSame("''", $default);
    }
}
