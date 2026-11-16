<?php

use PHPUnit\Framework\TestCase;

final class FormatSubmittedDateTest extends TestCase
{
    public function testEmptyStringBecomesUnknown(): void
    {
        $this->assertSame('Unknown', formatSubmittedDate(''));
    }

    public function testRealDateIsUnchanged(): void
    {
        $this->assertSame('2025-01-15 09:30:12', formatSubmittedDate('2025-01-15 09:30:12'));
    }

    public function testHtmlSpecialCharactersAreNotEscapedByHelper(): void
    {
        $this->assertSame('<b>&"', formatSubmittedDate('<b>&"'));
    }

    public function testWhitespaceOnlyIsUnchanged(): void
    {
        $this->assertSame('   ', formatSubmittedDate('   '));
    }
}
