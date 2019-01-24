<?php
declare(strict_types=1);
namespace UnitTests;

use Phap\Combinator as p;
use Phap\Result as r;
use PHPUnit\Framework\TestCase;

class CombinatorTest extends TestCase
{
    public function lit_provider(): array
    {
        return [
            ["123", null, r::make("23", ["1"])],
            ["123", "1", r::make("23", ["1"])],
            ["123", "2", null],
            ["", "2", null],
            ["", null, null],
        ];
    }
    /**
     * @dataProvider lit_provider
     */
    public function test_lit(string $input, ?string $char, ?r $expected): void
    {
        $actual = p::lit($char)($input);
        $this->assertEquals($expected, $actual);
    }

    public function or_provider(): array
    {
        $lit = function (?string $char = null): callable {
            return p::lit($char);
        };

        return [
            ["123", [$lit("1")], r::make("23", ["1"])],
            ["123", [$lit("2")], null],
            ["123", [$lit("1"), $lit("2")], r::make("23", ["1"])],
            ["123", [$lit("2"), $lit("1")], r::make("23", ["1"])],
            ["123", [$lit("3"), $lit("2"), $lit("1")], r::make("23", ["1"])],
            ["123", [$lit("2"), $lit("3")], null],
            ["", [$lit("2")], null],
            ["", [$lit("2"), $lit("3")], null],
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
        $lit = function (?string $char = null): callable {
            return p::lit($char);
        };

        return [
            ["123", [$lit("1")], r::make("23", ["1"])],
            ["123", [$lit("2")], null],
            ["123", [$lit("1"), $lit("2")], r::make("3", ["1", "2"])],
            [
                "123",
                [$lit("1"), $lit("2"), $lit("3")],
                r::make("", ["1", "2", "3"]),
            ],
            ["123", [$lit("2"), $lit("1")], null],
            ["123", [$lit("2"), $lit("3")], null],
            ["", [$lit("2")], null],
            ["", [$lit("2"), $lit("3")], null],
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
        $lit = function (?string $char = null): callable {
            return p::lit($char);
        };

        return [
            ["123", $lit("1"), r::make("23", ["1"])],
            ["123", $lit("2"), r::make("123", [])],
            ["1123", $lit("1"), r::make("23", ["1", "1"])],
            ["1123", $lit("2"), r::make("1123", [])],
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
        $lit = function (?string $char = null): callable {
            return p::lit($char);
        };

        return [
            ["123", $lit("2"), $lit("2"), $lit("3"), null],
            ["123", $lit("1"), $lit("2"), $lit("2"), null],
            ["123", $lit("1"), $lit("2"), $lit("3"), r::make("", ["2"])],
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
        $lit = function (?string $char = null): callable {
            return p::lit($char);
        };
        $toint = function (array $i): array {
            return array_map('intval', $i);
        };

        return [
            ["123", $toint, $lit("2"), null],
            ["123", $toint, $lit("1"), r::make("23", [1])],
            ["123", $toint, $lit("1"), r::make("23", ["1"])],
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
