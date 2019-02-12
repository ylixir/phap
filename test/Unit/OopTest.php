<?php
declare(strict_types=1);
namespace Test\Unit;

use Phap\Oop as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class OopTest extends TestCase
{
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
     * @dataProvider andProvider
     * @param array<int, p> $parsers
     */
    public function testAnd(string $input, array $parsers, ?r $expected): void
    {
        $actual = $parsers[0]->and(...array_slice($parsers, 1))($input);
        $this->assertEquals($expected, $actual);
    }

    public function dropProvider(): array
    {
        return [
            ["12", p::pop(), r::make('2', [])],
            ["12", p::lit("12"), r::make('', [])],
        ];
    }
    /**
     * @dataProvider dropProvider
     */
    public function testDrop(string $input, p $parser, ?r $expected): void
    {
        $actual = $parser->drop()($input);
        $this->assertEquals($expected, $actual);
    }

    public function endProvider(): array
    {
        return [
            ["12", p::pop(), null],
            ["12", p::lit("12"), r::make('', ["12"])],
        ];
    }
    /**
     * @dataProvider endProvider
     */
    public function testEnd(string $input, p $parser, ?r $expected): void
    {
        $actual = $parser->end()($input);
        $this->assertEquals($expected, $actual);
    }

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
     * @dataProvider litProvider
     */
    public function testLit(string $input, string $char, ?r $expected): void
    {
        $actual = p::lit($char)($input);
        $this->assertEquals($expected, $actual);
    }

    public function mapProvider(): array
    {
        return [
            ["123", 'intval', p::lit("2"), null],
            ["123", 'intval', p::lit("1"), r::make("23", [1])],
        ];
    }
    /**
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
     * @dataProvider orProvider
     * @param array<int, p> $parsers
     */
    public function testOr(string $input, array $parsers, ?r $expected): void
    {
        $actual = $parsers[0]->or(...array_slice($parsers, 1))($input);
        $this->assertEquals($expected, $actual);
    }

    public function popProvider(): array
    {
        return [
            ["123", r::make("23", ["1"])],
            ["😄∑♥😄", r::make("∑♥😄", ["😄"])],
            ["", null],
        ];
    }
    /**
     * @dataProvider popProvider
     */
    public function testPop(string $input, ?r $expected): void
    {
        $actual = p::pop()($input);
        $this->assertEquals($expected, $actual);
    }

    public function reduceProvider(): array
    {
        $reduce = function (array $a, string $s): array {
            if ('2' !== $s) {
                $a[] = $s;
            }

            return $a;
        };
        return [
            ["123", $reduce, p::lit("2"), null],
            ["123", $reduce, p::pop()->repeat(), r::make("", ["1", "3"])],
        ];
    }
    /**
     * @dataProvider reduceProvider
     */
    public function testReduce(
        string $input,
        callable $f,
        p $parser,
        ?r $expected
    ): void {
        $actual = $parser->reduce($f)($input);
        $this->assertEquals($expected, $actual);
    }

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
     * @dataProvider repeatProvider
     */
    public function testRepeat(string $input, p $parser, r $expected): void
    {
        $actual = $parser->repeat()($input);
        $this->assertEquals($expected, $actual);
    }
}
