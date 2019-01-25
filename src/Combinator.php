<?php
declare(strict_types=1);
namespace Phap;

use Phap\Result as r;

final class Combinator
{
    //convenience constants for passing functions to functions
    const and = self::class . "::and";
    const apply = self::class . "::apply";
    const between = self::class . "::between";
    const lit = self::class . "::lit";
    const manny = self::class . "::manny";
    const or = self::class . "::or";
    const pop = self::class . "::pop";

    /**
     * @return callable(string):?r
     */
    public static function pop(): callable
    {
        return function (string $in): ?r {
            if ("" === $in) {
                return null;
            } else {
                $head = [mb_substr($in, 0, 1)];
                $tail = mb_substr($in, 1);
                return r::make($tail, $head);
            }
        };
    }

    /**
     * @return callable(string):?r
     */
    public static function lit(string $c): callable
    {
        if ("" === $c) {
            return /** @return null */ function (string $in): ?r {
                    return null;
                };
        }

        return function (string $in) use ($c): ?r {
            $c_len = strlen($c);
            $head = mb_strcut($in, 0, $c_len);
            $tail = mb_strcut($in, $c_len);

            if ($head === $c) {
                return r::make($tail, [$head]);
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
            if (null === $head) {
                return null;
            }

            $tail = self::and(...$tail)($head->unparsed);
            if (null === $tail) {
                return null;
            }

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
            if (null === $left) {
                return null;
            }

            $middle = $middle($left->unparsed);
            if (null === $middle) {
                return null;
            }

            $right = $right($middle->unparsed);
            if (null === $right) {
                return null;
            }

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
