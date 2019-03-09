<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Functions as p;
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

        // create a parser for for a key-value pair that converts the key to the value
        $keyToValue = function (string $key, string $value): callable {
            $f = function (string $s) use ($value): string {
                return $value;
            };
            return p::map($f, p::lit($key));
        };

        // create a parser for each key-value pair we are given
        /** @var array<int,string> */
        $keys = array_keys($keyValues);
        $keyParsers = array_map($keyToValue, $keys, $keyValues);

        $open = p::drop(p::sequence(p::lit("{{"), p::spaces()));
        $close = p::drop(p::sequence(p::spaces(), p::lit("}}")));

        $interpolate = p::sequence(
            $open,
            p::alternatives(...$keyParsers),
            $close
        );

        $success = p::not(p::fail());
        $parser = p::block($success, p::end(), $interpolate);

        //parse it by passing the string to $munch
        return implode("", $parser($s)->parsed ?? [$s]);
    }

    public function interpolation_provider(): array
    {
        return [
            //standard case
            [
                "a {{     b   }} {{ c }}c",
                ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'],
                "a bar helloc",
            ],

            //edge cases
            ["abc", [], "abc"],
            ["a{{ b }}c", [], "a{{ b }}c"],
            [
                "a{{ d }}c",
                ['a' => 'foo', 'b' => 'bar', 'c' => 'hello'],
                "a{{ d }}c",
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
