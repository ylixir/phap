<?php
declare(strict_types=1);
namespace Test\Unit;

use Phap\Combinator as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class CombinatorTest extends TestCase
{
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

    public function many_provider(): array
    {
        return [
            ["123", p::lit("1"), r::make("23", ["1"])],
            ["123", p::lit("2"), r::make("123", [])],
            ["1123", p::lit("1"), r::make("23", ["1", "1"])],
            ["1123", p::lit("2"), r::make("1123", [])],
        ];
    }
    /**
     * @dataProvider many_provider
     */
    public function test_many(
        string $input,
        callable $parser,
        r $expected
    ): void {
        $actual = p::many($parser)($input);
        $this->assertEquals($expected, $actual);
    }

    public function between_provider(): array
    {
        return [
            ["123", p::lit("2"), p::lit("2"), p::lit("3"), null],
            ["123", p::lit("1"), p::lit("2"), p::lit("2"), null],
            ["123", p::lit("1"), p::lit("2"), p::lit("3"), r::make("", ["2"])],
        ];
    }
    /**
     * @dataProvider between_provider
     */
    public function test_between(
        string $input,
        callable $left,
        callable $middle,
        callable $right,
        ?r $expected
    ): void {
        $actual = p::between($left, $middle, $right)($input);
        $this->assertEquals($expected, $actual);
    }

    public function apply_provider(): array
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
}
