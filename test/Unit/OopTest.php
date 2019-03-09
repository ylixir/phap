<?php
declare(strict_types=1);
namespace Test\Unit;

use Phap\Oop as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class OopTest extends TestCase
{
    /**
     * @psalm-suppress DeprecatedClass
     */
    public function andProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider andProvider
     * @param array<int, p> $parsers
     */
    public function testAnd(string $input, array $parsers, ?r $expected): void
    {
        $actual = $parsers[0]->and(...array_slice($parsers, 1))($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function binaryProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider binaryProvider
     */
    public function testBinary(string $input, ?r $expected): void
    {
        $p = p::binary();

        self::assertEquals($expected, $p($input));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function blockProvider(): array
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
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider blockProvider
     */
    public function testBlock(
        callable $start,
        callable $end,
        callable $escape,
        string $in,
        ?r $expected
    ): void {
        $p = p::block($start, $end, $escape);

        self::assertEquals($expected, $p($in));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function dropProvider(): array
    {
        return [
            ["12", p::pop(), r::make('2', [])],
            ["12", p::lit("12"), r::make('', [])],
        ];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider dropProvider
     */
    public function testDrop(string $input, p $parser, ?r $expected): void
    {
        $actual = $parser->drop()($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function endProvider(): array
    {
        return [
            ["12", p::pop(), null],
            ["12", p::lit("12"), r::make('', ["12"])],
        ];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider endProvider
     */
    public function testEnd(string $input, p $parser, ?r $expected): void
    {
        $actual = $parser->end()($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function eolProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider eolProvider
     */
    public function testEol(string $input, ?r $expected): void
    {
        $p = p::eol();

        self::assertEquals($expected, $p($input));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function testFail(): void
    {
        $parser = p::fail();

        self::assertEquals(null, $parser("foo"));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function floatProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider floatProvider
     */
    public function testFloat(string $input, ?r $expected): void
    {
        $p = p::float();

        self::assertEquals($expected, $p($input));
    }
    /**
     * @psalm-suppress DeprecatedClass
     */
    public function foldProvider(): array
    {
        $fold = function (string $s, ...$a): array {
            if ('2' !== $s) {
                $a[] = $s;
            }

            return $a;
        };
        return [
            ["123", $fold, p::lit("2"), null],
            ["123", $fold, p::pop()->repeat(), r::make("", ["1", "3"])],
        ];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider foldProvider
     */
    public function testFold(
        string $input,
        callable $f,
        p $parser,
        ?r $expected
    ): void {
        $actual = $parser->fold($f)($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function hexProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider hexProvider
     */
    public function testHex(string $input, ?r $expected): void
    {
        $p = p::hex();

        self::assertEquals($expected, $p($input));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function intProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider intProvider
     */
    public function testInt(string $input, ?r $expected): void
    {
        $p = p::int();

        self::assertEquals($expected, $p($input));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function litProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider litProvider
     */
    public function testLit(string $input, string $char, ?r $expected): void
    {
        $actual = p::lit($char)($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function mapProvider(): array
    {
        return [
            ["123", 'intval', p::lit("2"), null],
            ["123", 'intval', p::lit("1"), r::make("23", [1])],
        ];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider mapProvider
     */
    public function testMap(
        string $input,
        callable $f,
        p $parser,
        ?r $expected
    ): void {
        $actual = $parser->map($f)($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function notProvider(): array
    {
        return [["foo", "foo", null], ["foo", "bar", r::make("bar", [])]];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider notProvider
     */
    public function testNot(string $lit, string $in, ?r $expected): void
    {
        $parser = p::not(p::lit($lit));

        self::assertEquals($expected, $parser($in));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function octalProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider octalProvider
     */
    public function testOctal(string $input, ?r $expected): void
    {
        $p = p::octal();

        self::assertEquals($expected, $p($input));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function orProvider(): array
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
     * @psalm-suppress DeprecatedClass
     * @dataProvider orProvider
     * @param array<int, p> $parsers
     */
    public function testOr(string $input, array $parsers, ?r $expected): void
    {
        $actual = $parsers[0]->or(...array_slice($parsers, 1))($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function popProvider(): array
    {
        return [
            ["123", r::make("23", ["1"])],
            ["😄∑♥😄", r::make("∑♥😄", ["😄"])],
            ["", null],
        ];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider popProvider
     */
    public function testPop(string $input, ?r $expected): void
    {
        $actual = p::pop()($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function repeatProvider(): array
    {
        return [
            ["123", p::lit("1"), r::make("23", ["1"])],
            ["123", p::lit("2"), r::make("123", [])],
            ["1123", p::lit("1"), r::make("23", ["1", "1"])],
            ["1123", p::lit("2"), r::make("1123", [])],
        ];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider repeatProvider
     */
    public function testRepeat(string $input, p $parser, r $expected): void
    {
        $actual = $parser->repeat()($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function spacesProvider(): array
    {
        return [[" \t", r::make("", [" ", "\t"])], ["", null]];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider spacesProvider
     */
    public function testSpaces(string $input, ?r $expected): void
    {
        $p = p::spaces();

        self::assertEquals($expected, $p($input));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function whitespaceProvider(): array
    {
        return [[" \t\r\n", r::make("", [" ", "\t", "\r\n"])], ["", null]];
    }
    /**
     * @psalm-suppress DeprecatedClass
     * @dataProvider whitespaceProvider
     */
    public function testWhitespace(string $input, ?r $expected): void
    {
        $p = p::whitespace();

        self::assertEquals($expected, $p($input));
    }
}
