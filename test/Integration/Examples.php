<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Combinator as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class Examples extends TestCase
{
    public static function integer_parser(): callable
    {
        $p = p::class;

        /** @var array<int,callable(string):?r> */
        $litDigits = array_map(p::lit, range("0", "9"));

        //any digit will do
        $anyDigit = p::or(...$litDigits);

        //we can have as many as we want, but we need at least one
        $allDigits = p::and($anyDigit, p::many($anyDigit));

        //convert the digits to actual integers
        $integer = p::apply(function (array $digits): array {
            return [(int) implode('', $digits)];
        }, $allDigits);

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

    /**
     * @var string[] $keyValues the keys are in the string between moustaches
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
        $spaces = p::many(p::lit(" "));
        $open = p::and(p::lit("{{"), $spaces);
        $close = p::and($spaces, p::lit("}}"));

        //parse the interpolation strings: only match keys passed in
        /** @var array<int,callable(string):?r> */
        $keyParsers = array_map(p::lit, array_keys($keyValues));
        $key = p::or(...$keyParsers);

        //extract the key from between the start and end tokens
        $interpolate = p::between($open, $key, $close);

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
        $value = p::apply($keysToValues, $interpolate);

        //try to interpolate, if we can't just eat a code point and try again
        $munch = p::many(p::or($value, p::pop()));

        //parse it by passing the string to $munch
        return implode("", $munch($s)->parsed ?? [$s]);
    }

    public function interpolation_provider(): array
    {
        return [
            ["abc", [], "abc"],
            ["a{{b}}c", [], "a{{b}}c"],
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
