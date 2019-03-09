<?php
declare(strict_types=1);
namespace Phap;

use Phap\Functions as p;
use Phap\Result as r;

/**
 * @deprecated
 */
final class Oop
{
    //convenience constants for passing functions to functions
    const binary = self::class . "::binary";
    const block = self::class . "::block";
    const eol = self::class . "::eol";
    const fail = self::class . "::fail";
    const float = self::class . "::float";
    const hex = self::class . "::hex";
    const int = self::class . "::int";
    const lit = self::class . "::lit";
    const not = self::class . "::not";
    const octal = self::class . "::octal";
    const pop = self::class . "::pop";
    const spaces = self::class . "::spaces";
    const whitespace = self::class . "::whitespace";

    /** @var callable(string):?r */
    private $parser;

    /** @param callable(string):?r $p */
    private function __construct(callable $p)
    {
        $this->parser = $p;
    }

    public function __invoke(string $s): ?r
    {
        return ($this->parser)($s);
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function and(self ...$tail): self
    {
        switch (count($tail)) {
            case 0:
                return $this;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = $tail[0]->and(...array_slice($tail, 1));
        }

        return new self(p::and($this->parser, $tail->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function binary(): self
    {
        return new self(p::binary());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function block(
        callable $start,
        callable $end,
        callable $escape
    ): self {
        return new self(p::block($start, $end, $escape));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function drop(): self
    {
        return new self(p::drop($this->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function end(): self
    {
        return new self(p::end($this->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function eol(): self
    {
        return new self(p::eol());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function fail(): self
    {
        return new self(p::fail());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function float(): self
    {
        return new self(p::float());
    }

    /**
     * @template T
     * @template S
     * @param callable(T, S...):S[] $f
     * @param array<int, S> $start
     * @psalm-suppress DeprecatedClass
     */
    public function fold(callable $f, array $start = []): self
    {
        return new self(p::fold($f, $start, $this->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function hex(): self
    {
        return new self(p::hex());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function int(): self
    {
        return new self(p::int());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function lit(string $c): self
    {
        return new self(p::lit($c));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function map(callable $f): self
    {
        return new self(p::map($f, $this->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function not(callable $p): self
    {
        return new self(p::not($p));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function octal(): self
    {
        return new self(p::octal());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function or(self ...$tail): self
    {
        switch (count($tail)) {
            case 0:
                return $this;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = $tail[0]->or(...array_slice($tail, 1));
        }
        return new self(p::or($this->parser, $tail->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function pop(): self
    {
        return new self(p::pop());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public function repeat(): self
    {
        return new self(p::repeat($this->parser));
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function spaces(): self
    {
        return new self(p::spaces());
    }

    /**
     * @psalm-suppress DeprecatedClass
     */
    public static function whitespace(): self
    {
        return new self(p::whitespace());
    }
}
