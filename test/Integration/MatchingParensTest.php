<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Functions as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class MatchingParensTest extends TestCase
{
    public static function match_parens(string $s): ?array
    {
        $match = function (string $s) use (&$match): ?r {
            /** @var callable(string):?r $match */
            return p::block(p::lit("("), p::lit(")"), $match)($s);
        };

        return p::sequence($match, p::end())($s)->parsed ?? null;
    }

    public function match_parens_provider(): array
    {
        return [
            //standard case
            ["( hello (world))", str_split("( hello (world))")],

            //edge cases
            ["()", ["(", ")"]],
            ["a", null],
            ["(()", null],
        ];
    }

    /**
     * @dataProvider match_parens_provider
     */
    public function test_match_parens(string $s, ?array $expected): void
    {
        $actual = self::match_parens($s);
        static::assertSame($expected, $actual);
    }
}
