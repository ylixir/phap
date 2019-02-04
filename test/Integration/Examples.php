<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Combinator as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class Examples extends TestCase
{
    public static function integerParser(): p
    {
        //any digit will do
        /** @var array<int,p> */
        $litDigits = array_map(p::lit, range("1", "9"));
        $anyDigit = p::lit("0")->or(...$litDigits);

        //we can have as many as we want, but we need at least one
        $allDigits = $anyDigit->with(p::many($anyDigit));

        //convert the digits to actual integers
        $integer = $allDigits->apply(function (array $digits): array {
            return [(int) implode('', $digits)];
        });

        return $integer;
    }

    public function parseIntegerProvider(): array
    {
        return [
            ["abc", null],
            ["1", r::make("", [1])],
            ["123", r::make("", [123])],
            ["12a", r::make("a", [12])],
            [" 1", null],
            ["1 ", r::make(" ", [1])],
            ["1 2", r::make(" 2", [1])],
        ];
    }
    /**
     * @dataProvider parseIntegerProvider
     */
    public function testParseInteger(string $in, ?r $expected): void
    {
        $parse = self::integerParser();
        static::assertEquals($expected, $parse($in));
    }

    /**
     * @var string[] $keyValues the keys are in the string between moustaches
     */
    public static function interpolateString(
        string $s,
        array $keyValues
    ): string {
        //don't even bother if there ase no keys to interpolate
        if ([] === $keyValues) {
            return $s;
        }

        // find the interpolation begin and end tokens
        $spaces = p::many(p::lit(" "));
        $open = p::lit("{{")->with($spaces);
        $close = $spaces->with(p::lit("}}"));

        //parse the interpolation strings: only match keys passed in
        /** @var array<int,p> */
        $keyParsers = array_map(p::lit, array_keys($keyValues));
        $key = $keyParsers[0]->or(...array_slice($keyParsers, 1));

        //extract the key from between the start and end tokens
        $interpolate = $key->between($open, $close);

        //function to convert some keys to values
        $keysToValues =
            /**
             * @param array<int, string> $keys
             * @return array<int, string>
             */
            function (array $keys) use ($keyValues): array {
                $v = [];
                foreach ($keys as $k) {
                    /** @var string */ $v[] = $keyValues[$k];
                }
                return $v;
            };

        // convert the inperpolated key tokens to their values
        $value = $interpolate->apply($keysToValues);

        //try to interpolate, if we can't just eat a code point and try again
        $munch = p::many($value->or(p::pop()));

        //parse it by passing the string to $munch
        return implode("", $munch($s)->parsed ?? [$s]);
    }

    public function interpolationProvider(): array
    {
        return [
            ["abc", [], "abc"],
            ["a{{b}}c", [], "a{{b}}c"],
            ["a{{b}}c", ['b' => 'abc'], "aabcc"],
            [
                "a{{d}}c",
                ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'],
                "a{{d}}c",
            ],
            ["a{{b}}c", ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'], "abarc"],
            [
                "a {{b}} c",
                ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'],
                "a bar c",
            ],
            ["a{{ b}}c", ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'], "abarc"],
            ["a{{b }}c", ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'], "abarc"],
            [
                "a{{     b   }}c",
                ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'],
                "abarc",
            ],
            [
                "{{a}}{{b}}{{c}}",
                ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'],
                "foobarhello",
            ],
        ];
    }
    /**
     * @dataProvider interpolationProvider
     */
    public function testInterpolation(
        string $s,
        array $kv,
        string $expected
    ): void {
        $actual = self::interpolateString($s, $kv);
        static::assertSame($expected, $actual);
    }
}
