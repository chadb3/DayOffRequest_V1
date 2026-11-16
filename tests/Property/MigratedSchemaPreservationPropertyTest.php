<?php

require_once __DIR__ . '/../MigratedDbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class MigratedSchemaPreservationPropertyTest extends MigratedDbTestCase
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
     * Feature: submitted-date-empty-fix, Property 2: Preservation (migrated field round trip, no date assertion)
     *
     * **Validates: Requirements 3.2, 3.3, 3.4**
     */
    public function testMigratedFieldRoundTrip(): void
    {
        $t = $this->text();
        $this->limitTo(100)
            ->forAll($t, $t, $t, $t, $t)
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason): void {
                $num = insertRequest($this->db, [
                    'name' => $name, 'phone' => $phone, 'shifts' => $shifts, 'sub' => $sub, 'reason' => $reason,
                ]);
                $row = getRequestById($this->db, $num);
                $this->assertNotNull($row);
                $this->assertSame('Pending', $row['STATUS']);
                $this->assertSame($name, $row['NAME']);
                $this->assertSame($phone, $row['PHONE']);
                $this->assertSame($shifts, $row['SHIFTS']);
                $this->assertSame($sub, $row['SUB']);
                $this->assertSame($reason, $row['REASON']);
            });
    }

    /**
     * Feature: submitted-date-empty-fix, Property 2: Preservation (archive copy, migrated schema, non-empty date)
     *
     * **Validates: Requirements 3.5, 3.7**
     */
    public function testArchiveCopiesFieldsOnMigratedSchema(): void
    {
        $t = $this->text();
        $date = Generators::map(
            static fn(int $ts): string => gmdate('Y-m-d H:i:s', $ts),
            Generators::choose(0, 4102444800)
        );
        $this->limitTo(100)
            ->forAll($t, $t, $t, $t, $t, $date, Generators::elements(['Approved', 'Denied']))
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason, string $d, string $status): void {
                $stmt = $this->db->prepare(
                    'INSERT INTO EMPDAYOFFREQ (NAME, PHONE, SHIFTS, SUB, REASON, SUBMITTED_DATE)
                     VALUES (:name, :phone, :shifts, :sub, :reason, :d)'
                );
                foreach (['name' => $name, 'phone' => $phone, 'shifts' => $shifts, 'sub' => $sub, 'reason' => $reason, 'd' => $d] as $k => $v) {
                    $stmt->bindValue(':' . $k, $v, SQLITE3_TEXT);
                }
                $this->assertNotFalse($stmt->execute());
                $num = $this->db->lastInsertRowID();
                $this->assertTrue(updateRequestStatus($this->db, $num, $status));
                $original = getRequestById($this->db, $num);
                $this->assertSame($d, $original['SUBMITTED_DATE']);
                $this->assertTrue(archiveRequest($this->db, $num));
                $archived = getArchivedRequestById($this->db, $num);
                $this->assertNotNull($archived);
                foreach (['NAME', 'PHONE', 'SHIFTS', 'SUB', 'REASON', 'STATUS', 'SUBMITTED_DATE'] as $col) {
                    $this->assertSame($original[$col], $archived[$col], $col);
                }
                $this->assertNull(getRequestById($this->db, $num));
            });
    }

    /**
     * Feature: submitted-date-empty-fix, Property 2: Preservation (backfilled legacy row keeps its date)
     *
     * **Validates: Requirements 3.8**
     */
    public function testBackfilledLegacyRowKeepsDateAfterInsert(): void
    {
        $t = $this->text();
        $this->limitTo(100)
            ->forAll($t, $t, $t, $t, $t, $t)
            ->then(function (string $legacyName, string $name, string $phone, string $shifts, string $sub, string $reason): void {
                $db = getDb(':memory:');
                try {
                    $this->createLegacyTable($db);
                    $stmt = $db->prepare('INSERT INTO EMPDAYOFFREQ (NAME, PHONE, REASON) VALUES (:n, :p, :r)');
                    $stmt->bindValue(':n', $legacyName, SQLITE3_TEXT);
                    $stmt->bindValue(':p', 'legacy', SQLITE3_TEXT);
                    $stmt->bindValue(':r', 'legacy', SQLITE3_TEXT);
                    $stmt->execute();
                    $legacyNum = $db->lastInsertRowID();

                    migrateSchema($db);
                    $before = getRequestById($db, $legacyNum);
                    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $before['SUBMITTED_DATE']);

                    insertRequest($db, ['name' => $name, 'phone' => $phone, 'shifts' => $shifts, 'sub' => $sub, 'reason' => $reason]);

                    $after = getRequestById($db, $legacyNum);
                    $this->assertSame($before, $after);
                } finally {
                    $db->close();
                }
            });
    }
}
