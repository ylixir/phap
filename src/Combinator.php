<?php
declare(strict_types=1);
namespace Phap;

use Phap\Result as r;

final class Combinator
{
    //convenience constants for passing functions to functions
    const lit = self::class . "::lit";
    const many = self::class . "::many";
    const pop = self::class . "::pop";

    /** @var callable(string):?r */ private $parse;

    /** @param callable(string):?r $p */
    private function __construct(callable $p)
    {
        $this->parse = $p;
    }

    public function __invoke(string $s): ?r
    {
        return ($this->parse)($s);
    }
    public static function pop(): self
    {
        return new self(function (string $in): ?r {
            if ("" === $in) {
                return null;
            } else {
                $head = [mb_substr($in, 0, 1)];
                $tail = mb_substr($in, 1);
                return r::make($tail, $head);
            }
        });
    }

    public static function lit(string $c): self
    {
        if ("" === $c) {
            return /** @return null */ new self(function (string $in): ?r {
                    return null;
                });
        }

        return new self(function (string $in) use ($c): ?r {
            $cLen = strlen($c);
            $head = mb_strcut($in, 0, $cLen);
            $tail = mb_strcut($in, $cLen);

            if ($head === $c) {
                return r::make($tail, [$head]);
            } else {
                return null;
            }
        });
    }

    //if we want __call to intercept functions, then apparently
    //there can't be static functions because php will just call them

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
        return new self(function (string $input) use ($tail): ?r {
            return ($this->parse)($input) ?? $tail($input);
        });
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

        return new self(function (string $input) use ($tail): ?r {
            $head = ($this->parse)($input);
            if (null === $head) {
                return null;
            }

            $tail = $tail($head->unparsed);
            if (null === $tail) {
                return null;
            }

            return r::make(
                $tail->unparsed,
                array_merge($head->parsed, $tail->parsed)
            );
        });
    }

    public static function many(self $parser): self
    {
        return new self(function (string $input) use ($parser): r {
            $parsed = [];
            $result = $parser($input);
            while (null !== $result) {
                $input = $result->unparsed;
                $parsed = array_merge($parsed, $result->parsed);
                $result = $parser($input);
            }

            return r::make($input, $parsed);
        });
    }

    public function between(self $left, self $right): self
    {
        return new self(function (string $input) use ($left, $right): ?r {
            $left = $left($input);
            if (null === $left) {
                return null;
            }

            $middle = ($this->parse)($left->unparsed);
            if (null === $middle) {
                return null;
            }

            $right = $right($middle->unparsed);
            if (null === $right) {
                return null;
            }

            return r::make($right->unparsed, $middle->parsed);
        });
    }

    /**
     * @param callable(array):array $f
     */
    public function apply(callable $f): self
    {
        return new self(function (string $input) use ($f): ?r {
            $result = ($this->parse)($input);
            return null === $result
                ? null
                : r::make($result->unparsed, $f($result->parsed));
        });
    }
}
