<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Functions as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class functional_examples extends TestCase
{
    public static function integer_parser(): callable
    {
        //any digit will do
        /** @var array<int,callable(string):?r> */
        $litDigits = array_map(p::lit, range("0", "9"));
        $anyDigit = p::or(...$litDigits);

        //we can have as many as we want, but we need at least one
        $allDigits = p::and($anyDigit, p::repeat($anyDigit));

        //convert the digits to actual integers from characters
        $intArray = p::map('intval', $allDigits);

        //reduce the separate digits into one
        $integer = p::fold(
            function (int $i, int $a, int ...$tail): array {
                return [$a * 10 + $i];
            },
            [0],
            $intArray
        );

        return $integer;
    }

    public function parse_integer_provider(): array
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
     * @dataProvider parse_integer_provider
     */
    public function test_parse_integer(string $in, ?r $expected): void
    {
        $parse = self::integer_parser();
        static::assertEquals($expected, $parse($in));
    }

    public function parse_ended_integer_provider(): array
    {
        return [["123", r::make("", [123])], ["12a", null]];
    }
    /**
     * @dataProvider parse_ended_integer_provider
     */
    public function test_parse_ended_integer(string $in, ?r $expected): void
    {
        //we can use end to make sure we don't have extra garbage
        $parse = p::end(self::integer_parser());
        static::assertEquals($expected, $parse($in));
    }

    /**
     * @var array<string,string> $keyValues the keys are in the string between moustaches
     */
    public static function interpolate_string(
        string $s,
        array $keyValues
    ): string {
        //don't even bother if there ase no keys to interpolate
        if ([] === $keyValues) {
            return $s;
        }

        // find the interpolation begin and end tokens
        $spaces = p::repeat(p::lit(" "));
        $open = p::drop(p::and(p::lit("{{"), $spaces));
        $close = p::drop(p::and($spaces, p::lit("}}")));

        //parse the interpolation strings: only match keys passed in
        /** @var array<int,callable(string):?r> */
        $keyParsers = array_map(p::lit, array_keys($keyValues));
        $key = p::or(...$keyParsers);

        //extract the key from between the start and end tokens
        $interpolate = p::and($open, $key, $close);

        //function to convert some keys to values
        $keyToValue = function (string $key) use ($keyValues): string {
            /** @var string */ $value = $keyValues[$key];
            return $value;
        };

        // convert the inperpolated key tokens to their values
        $value = p::map($keyToValue, $interpolate);

        //try to interpolate, if we can't just eat a code point and try again
        $munch = p::repeat(p::or($value, p::pop()));

        //parse it by passing the string to $munch
        return implode("", $munch($s)->parsed ?? [$s]);
    }

    public function interpolation_provider(): array
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
     * @dataProvider interpolation_provider
     */
    public function test_interpolation(
        string $s,
        array $kv,
        string $expected
    ): void {
        $actual = self::interpolate_string($s, $kv);
        static::assertSame($expected, $actual);
    }
}
