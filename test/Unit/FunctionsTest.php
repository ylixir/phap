<?php
declare(strict_types=1);
namespace Test\Unit;

use Phap\Functions as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function alternatives_provider(): array
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
     * @dataProvider alternatives_provider
     * @param array<int, callable(string):?r> $parsers
     */
    public function test_alternatives(
        string $input,
        array $parsers,
        ?r $expected
    ): void {
        $actual = p::alternatives(...$parsers)($input);
        $this->assertEquals($expected, $actual);
    }

    public function block_provider(): array
    {
        return [
            [
                p::lit('"'),
                p::lit('"'),
                p::lit('""'),
                '"1""2"',
                r::make("", ['"', '1', '""', '2', '"']),
            ],
            [
                p::lit('/*'),
                p::lit('*/'),
                p::fail(),
                "/*/*a*/",
                r::make("", ["/*", "/", "*", "a", "*/"]),
            ],
        ];
    }

    public function apply_provider(): array
    {
        $apply =
            /**
             * @param array<int,string> $s
             * @return array<int,string>
             */
            function (string ...$s): array {
                $a = [];
                foreach ($s as $v) {
                    if ('2' !== $v) {
                        $a[] = $v;
                    }
                }

                return $a;
            };
        return [
            ["123", $apply, p::lit("2"), null],
            ["123", $apply, p::repeat(p::pop()), r::make("", ["1", "3"])],
        ];
    }
    /**
     * @dataProvider apply_provider
     */
    public function test_apply(
        string $input,
        callable $f,
        callable $parser,
        ?r $expected
    ): void {
        $actual = p::apply($f, $parser)($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider block_provider
     */
    public function test_block(
        callable $start,
        callable $end,
        callable $escape,
        string $in,
        ?r $expected
    ): void {
        $p = p::block($start, $end, $escape);

        self::assertEquals($expected, $p($in));
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
        $actual = p::sequence($parser, p::end())($input);
        $this->assertEquals($expected, $actual);
    }

    public function eol_provider(): array
    {
        return [
            ["\n", r::make("", ["\n"])],
            ["\r\n", r::make("", ["\r\n"])],
            ["\r", r::make("", ["\r"])],
            ["\n\r", r::make("\r", ["\n"])],
            ["", null],
        ];
    }
    /**
     * @dataProvider eol_provider
     */
    public function test_eol(string $input, ?r $expected): void
    {
        $p = p::eol();

        self::assertEquals($expected, $p($input));
    }

    public function test_fail(): void
    {
        $parser = p::fail();

        self::assertEquals(null, $parser("foo"));
    }

    public function float_provider(): array
    {
        return [
            ["10.", r::make("", [10.0])],
            ["1.0", r::make("", [1.0])],
            [".1", r::make("", [0.1])],

            ["10E1", r::make("", [100.0])],
            ["10e-001", r::make("", [1.0])],
            ["10e+1", r::make("", [100.0])],

            ["10.e001", r::make("", [100.0])],
            ["1.00E-1", r::make("", [0.1])],
            [".1E+001", r::make("", [1.0])],

            ["1e00", r::make("", [1.0])],

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
            ["00", r::make("0", [0])],
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

    public function not_provider(): array
    {
        return [["foo", "foo", null], ["foo", "bar", r::make("bar", [])]];
    }
    /**
     * @dataProvider not_provider
     */
    public function test_not(string $lit, string $in, ?r $expected): void
    {
        $parser = p::not(p::lit($lit));

        self::assertEquals($expected, $parser($in));
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

    public function whitespace_provider(): array
    {
        return [[" \t\r\n", r::make("", [" ", "\t", "\r\n"])], ["", null]];
    }
    /**
     * @dataProvider whitespace_provider
     */
    public function test_whitespace(string $input, ?r $expected): void
    {
        $p = p::whitespace();

        self::assertEquals($expected, $p($input));
    }

    public function sequence_provider(): array
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
     * @dataProvider sequence_provider
     * @param array<int, callable(string):?r> $parsers
     */
    public function test_sequence(
        string $input,
        array $parsers,
        ?r $expected
    ): void {
        $actual = p::sequence(...$parsers)($input);
        $this->assertEquals($expected, $actual);
    }
}
