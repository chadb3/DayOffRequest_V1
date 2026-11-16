<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class StatusUpdatePreservesFieldsPropertyTest extends DbTestCase
{
    use TestTrait;

    private const REASONS = ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other'];

    /**
     * Feature: day-off-request, Property 5: Status update preserves all other fields
     *
     * **Validates: Requirements 3.1, 3.2**
     */
    public function testStatusUpdatePreservesAllOtherFields(): void
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
                Generators::elements(self::REASONS),
                Generators::elements(['Approved', 'Denied'])
            )
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason, string $newStatus): void {
                $num = insertRequest($this->db, [
                    'name' => $name,
                    'phone' => $phone,
                    'shifts' => $shifts,
                    'sub' => $sub,
                    'reason' => $reason,
                ]);

                $before = getRequestById($this->db, $num);
                $this->assertNotNull($before);
                $this->assertSame('Pending', $before['STATUS']);

                $this->assertTrue(updateRequestStatus($this->db, $num, $newStatus));

                $after = getRequestById($this->db, $num);
                $this->assertNotNull($after);
                $this->assertSame($newStatus, $after['STATUS']);

                foreach (['NUM', 'NAME', 'PHONE', 'SHIFTS', 'SUB', 'REASON', 'SUBMITTED_DATE'] as $field) {
                    $this->assertSame($before[$field], $after[$field], "$field changed");
                }
            });
    }
}
