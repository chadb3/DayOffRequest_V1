<?php

require_once __DIR__ . '/../MigratedDbTestCase.php';

/**
 * Stored-data preservation and full-flow integration on a migrated in-memory DB.
 * Validates: Requirements 2.4, 2.5, 3.8, 3.9
 */
final class SubmittedDateDisplayFlowTest extends MigratedDbTestCase
{
    private const DATE_RE = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';

    private function seedEmptyDateRow(): int
    {
        $this->db->exec("INSERT INTO EMPDAYOFFREQ (NAME, PHONE, REASON, SHIFTS, SUB, STATUS, SUBMITTED_DATE)
            VALUES ('Old', '555', 'legacy', 'AM', 'Bob', 'Pending', '')");
        return $this->db->lastInsertRowID();
    }

    private function sample(string $name): array
    {
        return ['name' => $name, 'phone' => '555-1234', 'shifts' => 'Mon', 'sub' => 'Sam', 'reason' => 'Trip'];
    }

    public function testEmptyDateRowSurvivesListApproveDenyAndSecondInsert(): void
    {
        $old = $this->seedEmptyDateRow();

        $pending = getPendingRequests($this->db);
        $this->assertSame('', $pending[0]['SUBMITTED_DATE']);
        $this->assertSame('Unknown', formatSubmittedDate($pending[0]['SUBMITTED_DATE']));

        $new = insertRequest($this->db, $this->sample('New'));
        $this->assertSame('', getRequestById($this->db, $old)['SUBMITTED_DATE']);
        $this->assertMatchesRegularExpression(self::DATE_RE, getRequestById($this->db, $new)['SUBMITTED_DATE']);

        $this->assertTrue(updateRequestStatus($this->db, $new, 'Denied'));
        $this->assertSame('', getRequestById($this->db, $old)['SUBMITTED_DATE']);

        $this->assertTrue(updateRequestStatus($this->db, $old, 'Approved'));
        $this->assertSame('', getRequestById($this->db, $old)['SUBMITTED_DATE']);
        $this->assertSame('', getHistoryRequests($this->db)[0]['SUBMITTED_DATE']);
    }

    public function testArchivingEmptyDateRowCopiesEmptyUnchanged(): void
    {
        $old = $this->seedEmptyDateRow();
        $this->assertTrue(updateRequestStatus($this->db, $old, 'Denied'));
        $this->assertTrue(archiveRequest($this->db, $old));

        $archived = getArchivedRequestById($this->db, $old);
        $this->assertNotNull($archived);
        $this->assertSame('', $archived['SUBMITTED_DATE']);
    }

    public function testFullFlowKeepsRealDateAtEveryStep(): void
    {
        $num = insertRequest($this->db, $this->sample('Flow'));

        $date = getRequestById($this->db, $num)['SUBMITTED_DATE'];
        $this->assertMatchesRegularExpression(self::DATE_RE, $date);

        $pending = getPendingRequests($this->db);
        $this->assertCount(1, $pending);
        $this->assertSame($date, $pending[0]['SUBMITTED_DATE']);
        $this->assertSame($date, formatSubmittedDate($pending[0]['SUBMITTED_DATE']));

        $this->assertTrue(updateRequestStatus($this->db, $num, 'Approved'));
        $this->assertSame($date, getRequestById($this->db, $num)['SUBMITTED_DATE']);
        $this->assertSame($date, getHistoryRequests($this->db)[0]['SUBMITTED_DATE']);

        $this->assertTrue(archiveRequest($this->db, $num));
        $archived = getArchivedRequestById($this->db, $num);
        $this->assertNotNull($archived);
        $this->assertSame($date, $archived['SUBMITTED_DATE']);
        $this->assertMatchesRegularExpression(self::DATE_RE, $archived['SUBMITTED_DATE']);
    }
}
