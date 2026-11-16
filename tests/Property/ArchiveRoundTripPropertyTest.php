<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class ArchiveRoundTripPropertyTest extends DbTestCase
{
    use TestTrait;

    /**
     * Feature: day-off-request, Property 6: Archive round-trip preserves data
     *
     * **Validates: Requirements 5.1, 5.2**
     */
    public function testArchiveRoundTripPreservesData(): void
    {
        $text = Generators::suchThat(
            static fn(string $s): bool => trim($s) !== '',
            Generators::string()
        );

        $this->limitTo(100)
            ->forAll(
                $text,
                $text,
                $text,
                $text,
                $text,
                Generators::elements(['Approved', 'Denied'])
            )
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason, string $status): void {
                $num = insertRequest($this->db, [
                    'name' => $name,
                    'phone' => $phone,
                    'shifts' => $shifts,
                    'sub' => $sub,
                    'reason' => $reason,
                ]);
                $this->assertTrue(updateRequestStatus($this->db, $num, $status));
                $original = getRequestById($this->db, $num);
                $this->assertNotNull($original);

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
