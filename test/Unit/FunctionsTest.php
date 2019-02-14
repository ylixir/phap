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

    public function fold_provider(): array
    {
        $fold = function (array $a, string $s): array {
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
}
