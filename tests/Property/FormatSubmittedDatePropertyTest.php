<?php

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('property')]
final class FormatSubmittedDatePropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: submitted-date-empty-fix, formatSubmittedDate returns non-empty input as-is.
     *
     * **Validates: Requirements 2.5, 3.9**
     */
    public function testNonEmptyStringReturnedAsIs(): void
    {
        $this->limitTo(100)
            ->forAll(Generators::suchThat(
                static fn(string $s): bool => $s !== '',
                Generators::string()
            ))
            ->then(function (string $s): void {
                $this->assertSame($s, formatSubmittedDate($s));
            });
    }

    /**
     * **Validates: Requirements 2.5**
     */
    public function testEmptyStringGivesUnknown(): void
    {
        $this->assertSame('Unknown', formatSubmittedDate(''));
    }
}
