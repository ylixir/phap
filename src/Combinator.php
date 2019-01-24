<?php
declare(strict_types=1);
namespace Phap;

use Phap\Result as r;

final class Combinator
{
    /**
     * @return callable(string):?r
     */
    public static function lit(?string $c = null): callable
    {
        $pop = function (string $in): ?r {
            if (strlen($in)) {
                $head = [substr($in, 0, 1)];
                $tail = substr($in, 1);
                return r::make($tail, $head);
            } else {
                return null;
            }
        };

        if (null === $c) {
            return $pop;
        }

        // at this point we know that $c is _not_ null

        return function (string $in) use ($c, $pop): ?r {
            $result = $pop($in);
            if ($c === $result->parsed[0] ?? null) {
                return $result;
            } else {
                return null;
            }
        };
    }

    /**
     * @param callable(string):?r $head
     * @param array<int, callable(string):?r> $tail
     * @return callable(string):?Result
     */
    public static function or(callable $head, callable ...$tail): callable
    {
        if ([] === $tail) {
            return $head;
        }

        return function (string $input) use ($head, $tail): ?r {
            return $head($input) ?? self::or(...$tail)($input);
        };
    }

    /**
     * @param callable(string):?r $head
     * @param array<int, callable(string):?r> $tail
     * @return callable(string):?r
     */
    public static function and(callable $head, callable ...$tail): callable
    {
        if ([] === $tail) {
            return $head;
        }

        return function (string $input) use ($head, $tail): ?r {
            $head = $head($input);
            if (null === $head) return null;

            $tail = self::and(...$tail)($head->unparsed);
            if (null === $tail) return null;

            return r::make(
                    $tail->unparsed,
                    array_merge($head->parsed, $tail->parsed)
                );
        };
    }

    /**
     * @param callable(string):?r $parser
     * @return callable(string):?r
     */
    public static function many(callable $parser): callable
    {
        return function (string $input) use ($parser): r {
            $parsed = [];
            $result = $parser($input);
            while (null !== $result) {
                $input = $result->unparsed;
                $parsed = array_merge($parsed, $result->parsed);
                $result = $parser($input);
            }

            return r::make($input, $parsed);
        };
    }

    /**
     * @param callable(string):?r $left
     * @param callable(string):?r $middle
     * @param callable(string):?r $right
     * @return callable(string):?r
     */
    public static function between(
        callable $left,
        callable $middle,
        callable $right
    ): callable {
        return function (string $input) use ($left, $middle, $right): ?r {
            $left = $left($input);
            if (null === $left) return null;

            $middle = $middle($left->unparsed);
            if (null === $middle) return null;

            $right = $right($middle->unparsed);
            if (null === $right) return null;

            return r::make($right->unparsed, $middle->parsed);
        };
    }

    /**
     * @param callable(array):array $f
     * @param callable(string):?r $parser
     * @return callable(string):?r
     */
    public static function apply(callable $f, callable $parser): callable
    {
        return function (string $input) use ($f, $parser): ?r {
            $result = $parser($input);
            return null === $result
                ? null
                : r::make($result->unparsed, $f($result->parsed));
        };
    }
}
