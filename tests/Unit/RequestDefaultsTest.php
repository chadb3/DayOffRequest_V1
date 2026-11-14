<?php

require_once __DIR__ . '/../DbTestCase.php';

final class RequestDefaultsTest extends DbTestCase
{
    private function sampleRequest(): array
    {
        return [
            'name' => 'Jane Doe',
            'phone' => '555-1234',
            'shifts' => 'Mon AM',
            'sub' => 'John Smith',
            'reason' => 'Medical',
        ];
    }

    /** Validates: Requirements 10.4 */
    public function testInsertedRequestDefaultsToPendingWithSubmittedDate(): void
    {
        $num = insertRequest($this->db, $this->sampleRequest());
        $row = getRequestById($this->db, $num);

        $this->assertNotNull($row);
        $this->assertSame('Pending', $row['STATUS']);
        $this->assertNotSame('', trim((string) $row['SUBMITTED_DATE']));
    }

    /** Validates: Requirements 10.6 */
    public function testPendingRequestsEmptyOnEmptyDatabase(): void
    {
        $this->assertSame([], getPendingRequests($this->db));
    }

    /** Validates: Requirements 10.6 */
    public function testHistoryRequestsEmptyOnEmptyDatabase(): void
    {
        $this->assertSame([], getHistoryRequests($this->db));
    }
}
