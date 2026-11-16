<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    private function valid(array $override = []): array
    {
        return array_merge([
            'name' => 'Jane Doe',
            'phone' => '555-1234',
            'shifts' => 'Mon AM',
            'sub' => 'John',
            'reason' => 'Medical',
        ], $override);
    }

    public function testValidInputHasNoErrors(): void
    {
        $this->assertSame([], validateRequest($this->valid()));
    }

    public static function fieldProvider(): array
    {
        return [['name'], ['phone'], ['shifts'], ['sub'], ['reason']];
    }

    #[DataProvider('fieldProvider')]
    public function testEmptyFieldIsRejected(string $field): void
    {
        $errors = validateRequest($this->valid([$field => '']));
        $this->assertSame([$field], array_keys($errors));
    }

    #[DataProvider('fieldProvider')]
    public function testMissingFieldIsRejected(string $field): void
    {
        $input = $this->valid();
        unset($input[$field]);
        $this->assertArrayHasKey($field, validateRequest($input));
    }

    #[DataProvider('fieldProvider')]
    public function testWhitespaceOnlyFieldIsRejected(string $field): void
    {
        $errors = validateRequest($this->valid([$field => " \t\n "]));
        $this->assertSame([$field], array_keys($errors));
    }

    public function testReasonOutsideAllowedListIsRejected(): void
    {
        $errors = validateRequest($this->valid(['reason' => 'Vacation']));
        $this->assertSame(['reason'], array_keys($errors));
    }

    public static function reasonProvider(): array
    {
        return array_map(static fn($r) => [$r], ALLOWED_REASONS);
    }

    #[DataProvider('reasonProvider')]
    public function testEachAllowedReasonIsAccepted(string $reason): void
    {
        $this->assertSame([], validateRequest($this->valid(['reason' => $reason])));
    }
}
