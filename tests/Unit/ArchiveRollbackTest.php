<?php

require_once __DIR__ . '/../DbTestCase.php';

/**
 * Archive rollback behavior.
 *
 * **Validates: Requirements 10.4, 10.6, 5.3**
 */
final class ArchiveRollbackTest extends DbTestCase
{
    private function approvedRequest(): int
    {
        $num = insertRequest($this->db, [
            'name' => 'Jane', 'phone' => '555-1234', 'shifts' => 'Mon AM',
            'sub' => 'Bob', 'reason' => 'Dentist',
        ]);
        $this->assertTrue(updateRequestStatus($this->db, $num, 'Approved'));
        return $num;
    }

    private function archiveRows(): array
    {
        $result = $this->db->query('SELECT * FROM EMPDAYOFFREQ_ARCHIVE ORDER BY NUM');
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function testArchiveInsertFailureLeavesBothTablesUnchanged(): void
    {
        $num = $this->approvedRequest();
        $before = getRequestById($this->db, $num);
        $this->db->exec(
            "INSERT INTO EMPDAYOFFREQ_ARCHIVE (NUM, NAME, PHONE, SHIFTS, SUB, REASON, STATUS, SUBMITTED_DATE, ARCHIVED_DATE)
             VALUES ($num, 'Old', '000', 'x', 'y', 'z', 'Denied', '2020-01-01 00:00:00', '2020-01-02 00:00:00')"
        );
        $archiveBefore = $this->archiveRows();

        $this->assertFalse(@archiveRequest($this->db, $num));

        $this->assertSame($before, getRequestById($this->db, $num));
        $this->assertSame($archiveBefore, $this->archiveRows());
    }

    public function testDeleteFailureRollsBackArchiveInsert(): void
    {
        $num = $this->approvedRequest();
        $before = getRequestById($this->db, $num);
        $this->db->exec(
            "CREATE TRIGGER block_delete BEFORE DELETE ON EMPDAYOFFREQ
             BEGIN SELECT RAISE(ABORT, 'delete blocked'); END"
        );

        $this->assertFalse(@archiveRequest($this->db, $num));

        $this->assertSame($before, getRequestById($this->db, $num));
        $this->assertSame([], $this->archiveRows());
    }

    public function testArchivingPendingRequestReturnsFalseAndChangesNothing(): void
    {
        $num = insertRequest($this->db, [
            'name' => 'Jane', 'phone' => '555-1234', 'shifts' => 'Mon AM',
            'sub' => 'Bob', 'reason' => 'Dentist',
        ]);
        $before = getRequestById($this->db, $num);

        $this->assertFalse(archiveRequest($this->db, $num));

        $this->assertSame($before, getRequestById($this->db, $num));
        $this->assertSame([], $this->archiveRows());
    }
}
