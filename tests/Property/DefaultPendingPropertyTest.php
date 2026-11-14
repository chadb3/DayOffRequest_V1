<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class DefaultPendingPropertyTest extends DbTestCase
{
    use TestTrait;

    private const REASONS = ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other'];

    /**
     * Feature: day-off-request, Property 1: Valid submission defaults to Pending
     *
     * **Validates: Requirements 1.1, 6.3**
     */
    public function testValidSubmissionDefaultsToPending(): void
    {
        // Non-empty, non-whitespace-only strings
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
                Generators::elements(self::REASONS)
            )
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason): void {
                $num = insertRequest($this->db, [
                    'name' => $name,
                    'phone' => $phone,
                    'shifts' => $shifts,
                    'sub' => $sub,
                    'reason' => $reason,
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
}
