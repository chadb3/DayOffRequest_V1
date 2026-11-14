<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class EmptyFieldValidationPropertyTest extends DbTestCase
{
    use TestTrait;

    private const FIELDS = ['name', 'phone', 'shifts', 'sub', 'reason'];
    private const REASONS = ['Medical', 'Dental', 'Family Emergency', 'Personal', 'Other'];

    /**
     * Feature: day-off-request, Property 3: Empty field validation rejects submission
     *
     * **Validates: Requirements 1.3**
     */
    public function testEmptyFieldValidationRejectsSubmission(): void
    {
        $text = Generators::suchThat(
            static fn(string $s): bool => trim($s) !== '',
            Generators::string()
        );
        // Empty or whitespace-only values (includes the empty string)
        $blank = Generators::map(
            static fn(array $chars): string => implode('', $chars),
            Generators::seq(Generators::elements(' ', "\t", "\n", "\r"))
        );

        $this->limitTo(100)
            ->forAll(
                $text,
                $text,
                $text,
                $text,
                Generators::elements(self::REASONS),
                $blank,
                Generators::subset(self::FIELDS)
            )
            ->when(static fn(string $a, string $b, string $c, string $d, string $e, string $f, array $blanked): bool => count($blanked) > 0)
            ->then(function (string $name, string $phone, string $shifts, string $sub, string $reason, string $blank, array $blanked): void {
                $data = [
                    'name' => $name,
                    'phone' => $phone,
                    'shifts' => $shifts,
                    'sub' => $sub,
                    'reason' => $reason,
                ];
                foreach ($blanked as $field) {
                    $data[$field] = $blank;
                }

                $before = countRequests($this->db);

                // Mirror dayOffRequest.php: only insert when validation passes
                $errors = validateRequest($data);
                if ($errors === []) {
                    insertRequest($this->db, $data);
                }

                $this->assertNotEmpty($errors, 'Submission with a blank field must be rejected');
                foreach ($blanked as $field) {
                    $this->assertArrayHasKey($field, $errors);
                }
                $this->assertSame($before, countRequests($this->db));
            });
    }
}
