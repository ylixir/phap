<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Oop as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class OopExamples extends TestCase
{
    public static function integerParser(): p
    {
        //any digit will do
        /** @var array<int,p> */
        $litDigits = array_map(p::lit, range("1", "9"));
        $anyDigit = p::lit("0")->or(...$litDigits);

        //we can have as many as we want, but we need at least one
        $allDigits = $anyDigit->with(p::all($anyDigit));

        //convert the digits to actual integers from characters
        $intArray = $allDigits->map('intval');
        //reduce the separate digits into one
        $integer = $intArray->reduce(
            /**
             * @param array{0:int} $a
             */
            function (array $a, int $i): array {
                return [$a[0] * 10 + $i];
            },
            [0]
        );

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

    public function parseEndedIntegerProvider(): array
    {
        return [["123", r::make("", [123])], ["12a", null]];
    }
    /**
     * @dataProvider parseEndedIntegerProvider
     */
    public function testParseEndedInteger(string $in, ?r $expected): void
    {
        //we can use end to make sure we don't have extra garbage
        $parse = self::integerParser()->end();
        static::assertEquals($expected, $parse($in));
    }

    /**
     * @var array<string,string> $keyValues the keys are in the string between moustaches
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
        $spaces = p::all(p::lit(" "));
        $open = p::lit("{{")
            ->with($spaces)
            ->drop();
        $close = $spaces->with(p::lit("}}"))->drop();

        //parse the interpolation strings: only match keys passed in
        /** @var array<int,p> */
        $keyParsers = array_map(p::lit, array_keys($keyValues));
        $key = $keyParsers[0]->or(...array_slice($keyParsers, 1));

        //extract the key from between the start and end tokens
        $interpolate = $open->with($key, $close);

        //function to convert some keys to values
        $keyToValue = function (string $key) use ($keyValues): string {
            /** @var string */ $value = $keyValues[$key];
            return $value;
        };

        // convert the inperpolated key tokens to their values
        $value = $interpolate->map($keyToValue);

        //try to interpolate, if we can't just eat a code point and try again
        $munch = p::all($value->or(p::pop()));

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
