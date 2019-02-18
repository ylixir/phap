<?php
declare(strict_types=1);
namespace Phap;

use Phap\Functions as p;
use Phap\Result as r;

final class Oop
{
    //convenience constants for passing functions to functions
    const int = self::class . "::int";
    const lit = self::class . "::lit";
    const pop = self::class . "::pop";

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

    public function drop(): self
    {
        return new self(p::drop($this->parser));
    }

    public function end(): self
    {
        return new self(p::end($this->parser));
    }

    public static function int(): self
    {
        return new self(p::int());
    }

    /**
     * @template T
     * @template S
     * @param callable(T, S...):S[] $f
     * @param array<int, S> $start
     */
    public function fold(callable $f, array $start = []): self
    {
        return new self(p::fold($f, $start, $this->parser));
    }

    public static function lit(string $c): self
    {
        return new self(p::lit($c));
    }

    public function map(callable $f): self
    {
        return new self(p::map($f, $this->parser));
    }

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

    public static function pop(): self
    {
        return new self(p::pop());
    }

    public function repeat(): self
    {
        return new self(p::repeat($this->parser));
    }
}
