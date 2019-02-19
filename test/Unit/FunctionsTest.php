<?php
declare(strict_types=1);
namespace Test\Unit;

use Phap\Functions as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function and_provider(): array
    {
        return [
            ["123", [p::lit("1")], r::make("23", ["1"])],
            ["123", [p::lit("2")], null],
            ["123", [p::lit("1"), p::lit("2")], r::make("3", ["1", "2"])],
            [
                "123",
                [p::lit("1"), p::lit("2"), p::lit("3")],
                r::make("", ["1", "2", "3"]),
            ],
            ["123", [p::lit("2"), p::lit("1")], null],
            ["123", [p::lit("2"), p::lit("3")], null],
            ["", [p::lit("2")], null],
            ["", [p::lit("2"), p::lit("3")], null],
        ];
    }
    /**
     * @dataProvider and_provider
     * @param array<int, callable(string):?r> $parsers
     */
    public function test_and(string $input, array $parsers, ?r $expected): void
    {
        $actual = p::and(...$parsers)($input);
        $this->assertEquals($expected, $actual);
    }

    public function binary_provider(): array
    {
        return [
            ["1", r::make("", [0b1])],
            ["0", r::make("", [0])],
            ["101a", r::make("a", [0b101])],
            ["", null],
            ["-1", null],
        ];
    }
    /**
     * @dataProvider binary_provider
     */
    public function test_binary(string $input, ?r $expected): void
    {
        $p = p::binary();

        self::assertEquals($expected, $p($input));
    }

    public function drop_provider(): array
    {
        return [
            ["12", p::pop(), r::make('2', [])],
            ["12", p::lit("12"), r::make('', [])],
        ];
    }
    /**
     * @dataProvider drop_provider
     */
    public function test_drop(
        string $input,
        callable $parser,
        ?r $expected
    ): void {
        $actual = p::drop($parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function end_provider(): array
    {
        return [
            ["12", p::pop(), null],
            ["12", p::lit("12"), r::make('', ["12"])],
        ];
    }
    /**
     * @dataProvider end_provider
     */
    public function test_end(
        string $input,
        callable $parser,
        ?r $expected
    ): void {
        $actual = p::end($parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function float_provider(): array
    {
        return [
            ["10.", r::make("", [10.0])],
            ["1.0", r::make("", [1.0])],
            [".1", r::make("", [0.1])],

            ["10E1", r::make("", [100.0])],
            ["10e-1", r::make("", [1.0])],
            ["10e+1", r::make("", [100.0])],

            ["10.e1", r::make("", [100.0])],
            ["1.0E-1", r::make("", [0.1])],
            [".1E+1", r::make("", [1.0])],

            ["1e0", r::make("", [1.0])],

            ["1.a", r::make("a", [1.0])],

            ["123", null],
            ["", null],
        ];
    }
    /**
     * @dataProvider float_provider
     */
    public function test_float(string $input, ?r $expected): void
    {
        $p = p::float();

        self::assertEquals($expected, $p($input));
    }

    public function fold_provider(): array
    {
        $fold = function (string $s, ...$a): array {
            if ('2' !== $s) {
                $a[] = $s;
            }

            return $a;
        };
        return [
            ["123", $fold, p::lit("2"), null],
            ["123", $fold, p::repeat(p::pop()), r::make("", ["1", "3"])],
        ];
    }
    /**
     * @dataProvider fold_provider
     */
    public function test_fold(
        string $input,
        callable $f,
        callable $parser,
        ?r $expected
    ): void {
        $actual = p::fold($f, [], $parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function hex_provider(): array
    {
        return [
            ["1a", r::make("", [0x1a])],
            ["F", r::make("", [0xf])],
            ["0", r::make("", [0])],
            ["0xa", r::make("xa", [0])],
            ["", null],
            ["-123", null],
        ];
    }
    /**
     * @dataProvider hex_provider
     */
    public function test_hex(string $input, ?r $expected): void
    {
        $p = p::hex();

        self::assertEquals($expected, $p($input));
    }

    public function int_provider(): array
    {
        return [
            ["123", r::make("", [123])],
            ["0", r::make("", [0])],
            ["123a", r::make("a", [123])],
            ["", null],
            ["-123", null],
        ];
    }
    /**
     * @dataProvider int_provider
     */
    public function test_int(string $input, ?r $expected): void
    {
        $p = p::int();

        self::assertEquals($expected, $p($input));
    }

    public function lit_provider(): array
    {
        return [
            ["123", "1", r::make("23", ["1"])],
            ["123", "12", r::make("3", ["12"])],
            ["123", "2", null],
            ["😄∑♥😄", "😄", r::make("∑♥😄", ["😄"])],
            ["😄∑♥😄", substr("😄", 0, 1), null],
            ["😄∑♥😄", substr("😄", 1, 1), null],
            ["", "2", null],
            "This case would be gross without special handling" => [
                "",
                "",
                null,
            ],
        ];
    }

    /**
     * @dataProvider lit_provider
     */
    public function test_lit(string $input, string $char, ?r $expected): void
    {
        $actual = p::lit($char)($input);
        $this->assertEquals($expected, $actual);
    }

    public function map_provider(): array
    {
        return [
            ["123", 'intval', p::lit("2"), null],
            ["123", 'intval', p::lit("1"), r::make("23", [1])],
        ];
    }
    /**
     * @dataProvider map_provider
     */
    public function test_map(
        string $input,
        callable $f,
        callable $parser,
        ?r $expected
    ): void {
        $actual = p::map($f, $parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function octal_provider(): array
    {
        return [
            ["123", r::make("", [0123])],
            ["0", r::make("", [0])],
            ["123a", r::make("a", [0123])],
            ["", null],
            ["-123", null],
        ];
    }
    /**
     * @dataProvider octal_provider
     */
    public function test_octal(string $input, ?r $expected): void
    {
        $p = p::octal();

        self::assertEquals($expected, $p($input));
    }

    public function or_provider(): array
    {
        return [
            ["123", [p::lit("1")], r::make("23", ["1"])],
            ["123", [p::lit("2")], null],
            ["123", [p::lit("1"), p::lit("2")], r::make("23", ["1"])],
            ["123", [p::lit("2"), p::lit("1")], r::make("23", ["1"])],
            [
                "123",
                [p::lit("3"), p::lit("2"), p::lit("1")],
                r::make("23", ["1"]),
            ],
            ["123", [p::lit("2"), p::lit("3")], null],
            ["", [p::lit("2")], null],
            ["", [p::lit("2"), p::lit("3")], null],
        ];
    }
    /**
     * @dataProvider or_provider
     * @param array<int, callable(string):?r> $parsers
     */
    public function test_or(string $input, array $parsers, ?r $expected): void
    {
        $actual = p::or(...$parsers)($input);
        $this->assertEquals($expected, $actual);
    }

    public function pop_provider(): array
    {
        return [
            ["123", r::make("23", ["1"])],
            ["😄∑♥😄", r::make("∑♥😄", ["😄"])],
            ["", null],
        ];
    }
    /**
     * @dataProvider pop_provider
     */
    public function test_pop(string $input, ?r $expected): void
    {
        $actual = p::pop()($input);
        $this->assertEquals($expected, $actual);
    }

    public function repeat_provider(): array
    {
        return [
            ["123", p::lit("1"), r::make("23", ["1"])],
            ["123", p::lit("2"), r::make("123", [])],
            ["1123", p::lit("1"), r::make("23", ["1", "1"])],
            ["1123", p::lit("2"), r::make("1123", [])],
        ];
    }
    /**
     * @dataProvider repeat_provider
     */
    public function test_repeat(
        string $input,
        callable $parser,
        r $expected
    ): void {
        $actual = p::repeat($parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function spaces_provider(): array
    {
        return [[" \t", r::make("", [" ", "\t"])], ["", null]];
    }
    /**
     * @dataProvider spaces_provider
     */
    public function test_spaces(string $input, ?r $expected): void
    {
        $p = p::spaces();

        self::assertEquals($expected, $p($input));
    }
}
