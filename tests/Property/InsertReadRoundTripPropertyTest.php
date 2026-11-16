<?php

require_once __DIR__ . '/../DbTestCase.php';

use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\Attributes\Group;

#[Group('property')]
final class InsertReadRoundTripPropertyTest extends DbTestCase
{
    use TestTrait;

    private const FRAGMENTS = [
        "O'Brien", 'say "hi"', "back\\slash", "semi;colon --", "'; DROP TABLE EMPDAYOFFREQ;--",
        'José Müller', '日本語テキスト', 'Привет мир', 'مرحبا', '😀🎉', "tab\there", "line\nbreak",
        '%_LIKE%', '<b>&amp;</b>', 'a', ' padded ',
    ];

    private function recordGenerator(): \Eris\Generator
    {
        $text = $this->textGenerator();
        return Generators::map(
            static fn(array $f): array => [
                'name' => $f[0], 'phone' => $f[1], 'shifts' => $f[2], 'sub' => $f[3], 'reason' => $f[4],
            ],
            Generators::tuple($text, $text, $text, $text, $text)
        );
    }

    private function textGenerator(): \Eris\Generator
    {
        return Generators::map(
            static function (array $t): string {
                [$parts, $repeat] = $t;
                $s = implode(' ', $parts);
                return str_repeat($s, $repeat);
            },
            Generators::tuple(
                Generators::vector(3, Generators::oneOf(
                    Generators::elements(self::FRAGMENTS),
                    Generators::map(
                        static fn(string $s): string => 'r' . str_replace("\0", '', $s),
                        Generators::string()
                    )
                )),
                Generators::elements([1, 1, 1, 5, 50, 300])
            )
        );
    }

    /**
     * Feature: day-off-request, Property 7: Insert-then-read round trip
     *
     * **Validates: Requirements 10.5**
     */
    public function testInsertThenReadRoundTrip(): void
    {
        $this->limitTo(100)
            ->forAll(Generators::vector(3, $this->recordGenerator()))
            ->then(function (array $records): void {
                $nums = [];
                foreach ($records as $i => $rec) {
                    $nums[$i] = insertRequest($this->db, $rec);
                }

                $this->assertCount(3, array_unique($nums));

                foreach ($records as $i => $rec) {
                    $row = getRequestById($this->db, $nums[$i]);
                    $this->assertNotNull($row);
                    $this->assertSame($nums[$i], (int) $row['NUM']);
                    $this->assertSame($rec['name'], $row['NAME']);
                    $this->assertSame($rec['phone'], $row['PHONE']);
                    $this->assertSame($rec['shifts'], $row['SHIFTS']);
                    $this->assertSame($rec['sub'], $row['SUB']);
                    $this->assertSame($rec['reason'], $row['REASON']);
                }
            });
    }
}
