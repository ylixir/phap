<?php
declare(strict_types=1);
namespace Test\Integration;

use Phap\Functions as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    /**
     * @return null|bool|number|string|array|object
     */
    public static function parse_json(string $s)
    {
        /**
         * The simplest things for json to parse are the literals
         */
        $true = p::map(function (string $t): bool {
            return true;
        }, p::lit("true"));
        $false = p::map(function (string $f): bool {
            return false;
        }, p::lit("false"));
        $null = p::map(function (string $n): ?bool {
            return null;
        }, p::lit("null"));

        /**
         * For numbers we can rely mostly on the built in parsers.
         * we need to add negatives though, since the built in parser doesn't
         */
        $number = p::alternatives(p::float(), p::int());
        $negativeNumber = p::sequence(p::drop(p::lit("-")), $number);
        $negativeNumber = P::map(
            /**
             * @param float|int $f
             * @return float|int
             */
            function ($f) {
                return -$f;
            },
            $negativeNumber
        );
        $number = p::alternatives($number, $negativeNumber);

        /**
         * here we build it the string parser.
         * first we make a parser to parse unicode,
         * then a parser for escaped literals
         * next a parser for forbidden values
         * then we put it all together in a sequence
         * finally we flatten from array to string
         */

        /** @var array<int,callable(string):?r> */
        $hexDigits = array_merge(
            array_map(p::lit, range(0, 9)),
            array_map(p::lit, range('a', 'z')),
            array_map(p::lit, range('A', 'Z'))
        );
        $hexDigit = p::alternatives(...$hexDigits);
        $unicode = p::sequence(
            p::drop(p::lit("u")),
            $hexDigit,
            $hexDigit,
            $hexDigit,
            $hexDigit
        );
        $unicode = p::fold(
            function (string $digit, string $digits): array {
                return [$digits . $digit];
            },
            [''],
            $unicode
        );
        $unicode = p::map(function (string $s): string {
            return iconv('UCS-2', 'UTF-8', $s);
        }, $unicode);

        $literalsMap = [
            "\"" => "\"",
            "\\" => "\\",
            "/" => "/",
            "b" => chr(8),
            "f" => "\f",
            "n" => "\n",
            "r" => "\r",
            "t" => "\t",
        ];
        $literal = function (string $key, string $value): callable {
            return p::map(function (string $k) use ($value): string {
                return $value;
            }, p::lit($key));
        };
        /** @var array<int,callable(string):?r> */
        $literals = array_map(
            $literal,
            array_keys($literalsMap),
            array_values($literalsMap)
        );

        $escape = p::sequence(
            p::drop(p::lit("\\")),
            p::alternatives($unicode, ...$literals)
        );

        /** @var array<int,callable(string):?r> */
        $forbidden = array_merge(
            array_map(p::lit, array_values($literalsMap)),
            array_map(p::lit, range(chr(0x0), chr(0x1f)))
        );
        $forbidden = p::alternatives(...$forbidden);

        $string = p::repeat(
            p::alternatives($escape, p::sequence(p::not($forbidden), p::pop()))
        );
        $string = p::sequence(
            p::drop(p::lit("\"")),
            $string,
            p::drop(p::lit("\""))
        );
        $string = p::fold(
            function (string $new, string $old): array {
                return [$old . $new];
            },
            [''],
            $string
        );

        /**
         * this parser basically just eats whitespace
         */
        $empty = p::drop(p::alternatives(p::whitespace(), p::not(p::fail())));

        /**
         * this is the meat of the parser.
         * $array and $object depend on this, but this
         * depends on $array and object. we solve this
         * by passing these by reference and defining after
         */
        $value = function (string $s) use (
            $empty,
            $true,
            $false,
            $null,
            $number,
            $string,
            &$array,
            &$object
        ): ?r {
            /**
             * @var callable(string):?r $array
             * @var callable(string):?r $object
             */

            return p::sequence(
                $empty,
                p::alternatives(
                    $true,
                    $false,
                    $null,
                    $number,
                    $string,
                    $array,
                    $object
                ),
                $empty
            )($s);
        };

        $array = p::sequence(
            $value,
            p::repeat(p::sequence(p::drop(p::lit(",")), $value))
        );
        $array = p::sequence(
            p::drop(p::lit("[")),
            p::alternatives($array, $empty),
            p::drop(p::lit("]"))
        );
        $array = p::fold(
            /** @param mixed $new */
            function ($new, array $old): array {
                $result = array_merge($old, [$new]);
                return [$result];
            },
            [[]],
            $array
        );

        $key = p::sequence($empty, $string, $empty);
        //an object is made up of key value pairs
        $keyValue = p::sequence($key, p::drop(p::lit(":")), $value);
        //we flatten the key value pairs to actually be pairs
        $keyValue = p::fold(
            /** @param mixed $new */
            function ($new, array $old): array {
                return [array_merge($old, [$new])];
            },
            [[]],
            $keyValue
        );
        $keyValue = p::map(function (array $pair): array {
            return [$pair[0] => $pair[1]];
        }, $keyValue);
        $object = p::sequence(
            $keyValue,
            p::repeat(p::sequence(p::drop(p::lit(",")), $keyValue))
        );
        $object = p::sequence(
            p::drop(p::lit("{")),
            p::alternatives($object, $empty),
            p::drop(p::lit("}"))
        );
        $object = p::fold(
            function (array $new, array $old): array {
                return [array_merge($old, $new)];
            },
            [[]],
            $object
        );
        $object = p::map(function (array $object): object {
            return (object) $object;
        }, $object);

        return p::sequence($value, p::end())($s)->parsed ?? null;
    }

    public function json_provider(): array
    {
        return [
            //standard case
            [""],
            ["true"],
            ["false"],
            ["null"],
            ["\u002f"],
            ["1"],
            ['"foo"'],
            ["[]"],
            ["{}"],
            ['[1,"foo",{"hello":"world","bar":[1.0,-1,-1e5]}]'],

            //edge cases
            ["--1"],
            ["[[]"],
            ["[{]"],
        ];
    }

    /**
     * @dataProvider json_provider
     */
    public function test_json(string $s): void
    {
        /** @var null|array{0:null|bool|number|string|array|object} */
        $actual = self::parse_json($s);
        /** @var null|bool|number|string|array|object */
        $expected = json_decode($s);
        if (null === $actual) {
            static::assertNull($expected);
        } else {
            static::assertEquals($expected, $actual[0]);
        }
    }
}
