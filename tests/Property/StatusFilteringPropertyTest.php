<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class StatusFilteringPropertyTest extends DbTestCase
{
    use TestTrait;

    private const REASONS = ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other'];
    private const STATUSES = ['Pending', 'Approved', 'Denied'];

    /**
     * Feature: day-off-request, Property 4: Status filtering returns correct subset
     *
     * **Validates: Requirements 2.1, 4.1**
     */
    public function testStatusFilteringReturnsCorrectSubset(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generators::seq(Generators::elements(self::STATUSES)),
                Generators::elements(self::REASONS)
            )
            ->then(function (array $statuses, string $reason): void {
                // Fresh table per iteration
                $this->db->exec('DELETE FROM EMPDAYOFFREQ');

                $expected = [];
                foreach ($statuses as $i => $status) {
                    $num = insertRequest($this->db, [
                        'name' => "Name $i",
                        'phone' => "555-000$i",
                        'shifts' => "Shift $i",
                        'sub' => "Sub $i",
                        'reason' => $reason,
                    ]);
                    if ($status !== 'Pending') {
                        $this->assertTrue(updateRequestStatus($this->db, $num, $status));
                    }
                    $expected[$num] = $status;
                }

                $pending = getPendingRequests($this->db);
                $history = getHistoryRequests($this->db);

                foreach ($pending as $row) {
                    $this->assertSame('Pending', $row['STATUS']);
                }
                foreach ($history as $row) {
                    $this->assertContains($row['STATUS'], ['Approved', 'Denied']);
                }

                $pendingNums = array_map('intval', array_column($pending, 'NUM'));
                $historyNums = array_map('intval', array_column($history, 'NUM'));

                // No overlap
                $this->assertSame([], array_values(array_intersect($pendingNums, $historyNums)));

                // Union equals the full record set
                $union = array_merge($pendingNums, $historyNums);
                sort($union);
                $all = array_keys($expected);
                sort($all);
                $this->assertSame($all, $union);
                $this->assertSame(countRequests($this->db), count($union));

                // Each row landed in the right bucket
                foreach ($pendingNums as $n) {
                    $this->assertSame('Pending', $expected[$n]);
                }
                foreach ($historyNums as $n) {
                    $this->assertNotSame('Pending', $expected[$n]);
                }
            });
    }
}
