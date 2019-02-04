<?php
declare(strict_types=1);
namespace Test\Unit;

use Phap\Combinator as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class CombinatorTest extends TestCase
{
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
        $actual = $parsers[0]->with(...array_slice($parsers, 1))($input);
        $this->assertEquals($expected, $actual);
    }

    public function manyProvider(): array
    {
        return [
            ["123", p::lit("1"), r::make("23", ["1"])],
            ["123", p::lit("2"), r::make("123", [])],
            ["1123", p::lit("1"), r::make("23", ["1", "1"])],
            ["1123", p::lit("2"), r::make("1123", [])],
        ];
    }
    /**
     * @dataProvider manyProvider
     */
    public function testMany(string $input, p $parser, r $expected): void
    {
        $actual = p::all($parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function betweenProvider(): array
    {
        return [
            ["123", p::lit("2"), p::lit("2"), p::lit("3"), null],
            ["123", p::lit("1"), p::lit("2"), p::lit("2"), null],
            ["123", p::lit("1"), p::lit("2"), p::lit("3"), r::make("", ["2"])],
        ];
    }
    /**
     * @dataProvider betweenProvider
     */
    public function testBetween(
        string $input,
        p $left,
        p $middle,
        p $right,
        ?r $expected
    ): void {
        $actual = $middle->between($left, $right)($input);
        $this->assertEquals($expected, $actual);
    }

    public function applyProvider(): array
    {
        $toint = function (array $i): array {
            return array_map('intval', $i);
        };

        return [
            ["123", $toint, p::lit("2"), null],
            ["123", $toint, p::lit("1"), r::make("23", [1])],
            ["123", $toint, p::lit("1"), r::make("23", ["1"])],
        ];
    }
    /**
     * @dataProvider applyProvider
     */
    public function testApply(
        string $input,
        callable $f,
        p $parser,
        ?r $expected
    ): void {
        $actual = $parser->apply($f)($input);
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
    public function testdrop(string $input, p $parser, ?r $expected): void
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
}
