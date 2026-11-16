<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class FreshSchemaPreservationPropertyTest extends DbTestCase
{
    use TestTrait;

    private function text()
    {
        return Generators::suchThat(
            static fn(string $s): bool => trim($s) !== '',
            Generators::string()
        );
    }

    /**
     * Feature: submitted-date-empty-fix, Property 2: Preservation (fresh-schema insert)
     *
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     */
    public function testFreshSchemaInsertPreserved(): void
    {
        $t = $this->text();
        $this->limitTo(100)
            ->forAll($t, $t, $t, $t, $t)
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason): void {
                $in = ['name' => $name, 'phone' => $phone, 'shifts' => $shifts, 'sub' => $sub, 'reason' => $reason];
                $num = insertRequest($this->db, $in);
                $row = getRequestById($this->db, $num);
                $this->assertNotNull($row);
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['SUBMITTED_DATE']);
                $now = $this->db->querySingle("SELECT datetime('now','localtime')");
                $this->assertLessThanOrEqual(5, abs(strtotime($now . ' UTC') - strtotime($row['SUBMITTED_DATE'] . ' UTC')));
                $this->assertSame('Pending', $row['STATUS']);
                $this->assertSame($name, $row['NAME']);
                $this->assertSame($phone, $row['PHONE']);
                $this->assertSame($shifts, $row['SHIFTS']);
                $this->assertSame($sub, $row['SUB']);
                $this->assertSame($reason, $row['REASON']);
            });
    }

    /**
     * Feature: submitted-date-empty-fix, Property 2: Preservation (archive copy, fresh schema)
     *
     * **Validates: Requirements 3.5, 3.7**
     */
    public function testArchiveCopiesFieldsOnFreshSchema(): void
    {
        $t = $this->text();
        $this->limitTo(100)
            ->forAll($t, $t, $t, $t, $t, Generators::elements(['Approved', 'Denied']))
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason, string $status): void {
                $num = insertRequest($this->db, [
                    'name' => $name, 'phone' => $phone, 'shifts' => $shifts, 'sub' => $sub, 'reason' => $reason,
                ]);
                $this->assertTrue(updateRequestStatus($this->db, $num, $status));
                $original = getRequestById($this->db, $num);
                $this->assertNotSame('', $original['SUBMITTED_DATE']);
                $this->assertTrue(archiveRequest($this->db, $num));
                $archived = getArchivedRequestById($this->db, $num);
                $this->assertNotNull($archived);
                foreach (['NAME', 'PHONE', 'SHIFTS', 'SUB', 'REASON', 'STATUS', 'SUBMITTED_DATE'] as $col) {
                    $this->assertSame($original[$col], $archived[$col], $col);
                }
                $this->assertNull(getRequestById($this->db, $num));
            });
    }
}
