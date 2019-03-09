<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Functions as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class functional_examples extends TestCase
{
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
