<?php
declare(strict_types=1);
namespace Phap;

use Phap\Functions as p;
use Phap\Result as r;

final class Oop
{
    //convenience constants for passing functions to functions
    const all = self::class . "::all";
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

    public static function all(self $p): self
    {
        return new self(p::all($p->parser));
    }

    public function drop(): self
    {
        return new self(p::drop($this->parser));
    }

    public function end(): self
    {
        return new self(p::end($this->parser));
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

    /**
     * @param callable(array, mixed):array $f
     */
    public function reduce(callable $f, array $start = []): self
    {
        return new self(p::reduce($f, $start, $this->parser));
    }

    public function with(self ...$tail): self
    {
        switch (count($tail)) {
            case 0:
                return $this;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = $tail[0]->with(...array_slice($tail, 1));
        }

        return new self(p::with($this->parser, $tail->parser));
    }
}
