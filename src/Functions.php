<?php
declare(strict_types=1);
namespace Phap;

use Phap\Result as r;

final class Functions
{
    //convenience constants for passing functions to functions
    const and = self::class . "::and";
    const drop = self::class . "::drop";
    const end = self::class . "::end";
    const fold = self::class . "::fold";
    const int = self::class . "::int";
    const lit = self::class . "::lit";
    const map = self::class . "::map";
    const or = self::class . "::or";
    const pop = self::class . "::pop";
    const repeat = self::class . "::repeat";

    /**
     * @param callable(string):?r $head
     * @param array<int,callable(string):?r> $tail
     * @return callable(string):?r
     */
    public static function and(callable $head, callable ...$tail): callable
    {
        switch (count($tail)) {
            case 0:
                return $head;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = self::and(...$tail);
        }

        return function (string $input) use ($head, $tail): ?r {
            $head = $head($input);
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
        };
    }

    /**
     * @param callable(string):?r $p
     * @return callable(string):?r
     */
    public static function drop(callable $p): callable
    {
        return function (string $in) use ($p): ?r {
            $r = $p($in);

            if (null === $r) {
                return null;
            } else {
                return r::make($r->unparsed, []);
            }
        };
    }

    /**
     * @param callable(string):?r $p
     * @return callable(string):?r
     */
    public static function end(callable $p): callable
    {
        return function (string $in) use ($p): ?r {
            $r = $p($in);

            if (null === $r || '' !== $r->unparsed) {
                return null;
            } else {
                return $r;
            }
        };
    }

    /**
     * @return callable(string):?r<float>
     */
    public static function float(): callable
    {
        $decimal_part = self::map(function (int $q): float {
            for ($d = 1; $d <= $q; $d *= 10);
            return $q / $d;
        }, self::int());
        $integer_part = self::map('floatval', self::int());

        // parse a non-scientific float into integer and decimal parts
        $parts = self::or(
            self::and($integer_part, self::drop(self::lit(".")), $decimal_part),
            self::and($integer_part, self::drop(self::lit("."))),
            self::and(self::drop(self::lit(".")), $decimal_part)
        );
        $float = self::fold(
            /** @return array{0:float} */ function (float $p, float $i): array {
                return [$i + $p];
            },
            [0.0],
            $parts
        );

        $e = self::drop(self::or(self::lit("e"), self::lit("E")));
        $negative_integer = self::map(function (int $i): int {
            return -$i;
        }, self::and(self::drop(self::lit("-")), self::int()));
        $positive_integer = self::and(self::drop(self::lit("+")), self::int());
        $mantissa = self::map(function (int $i): float {
            return pow(10, $i);
        }, self::and(
            $e,
            self::or(self::int(), $negative_integer, $positive_integer)
        ));
        $scientific = self::fold(
            /** @return array{0:float} */ function (float $a, float $b): array {
                return [$a * $b];
            },
            [1],
            self::and(self::or($float, $integer_part), $mantissa)
        );

        return self::or($scientific, $float);
    }

    /**
     * @template S
     * @template T
     * @param callable(T, S...):array<int,S> $f
     * @param callable(string):?r $p
     * @return callable(string):?r
     */
    public static function fold(
        callable $f,
        array $start,
        callable $p
    ): callable {
        return function (string $in) use ($f, $p, $start): ?r {
            $r = $p($in);
            if (null === $r) {
                return $r;
            } else {
                $ftransform =
                    /**
                     * @param S[] $a
                     * @param T $s
                     * @return array<int, S>
                     */
                    function (array $a, $s) use ($f): array {
                        return $f($s, ...$a);
                    };
                /** @var array<int, S> */ $reduced = array_reduce(
                    $r->parsed,
                    $ftransform,
                    $start
                );
                return r::make($r->unparsed, $reduced);
            }
        };
    }

    /**
     * @return callable(string):?r<int>
     */
    public static function int(): callable
    {
        /**
         * @var array<int, callable(string):?r<string>>
         */
        $digitLits = array_map(self::lit, range("0", "9"));
        $digits = self::or(...$digitLits);

        $intString = self::and($digits, self::repeat($digits));

        $intVal = function (string $d, int $a): array {
            return [$a * 10 + (int) $d];
        };

        return self::fold($intVal, [0], $intString);
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
            $cLen = strlen($c);
            $head = mb_strcut($in, 0, $cLen);
            $tail = mb_strcut($in, $cLen);

            if ($head === $c) {
                return r::make($tail, [$head]);
            } else {
                return null;
            }
        };
    }

    /**
     * @template S
     * @template T
     * @param callable(S):T $f
     * @param callable(string):?r $p
     * @return callable(string):?r
     */
    public static function map(callable $f, callable $p): callable
    {
        return function (string $in) use ($f, $p): ?r {
            $r = $p($in);
            if (null === $r) {
                return $r;
            } else {
                /** @var array<int, T> */ $mapped = array_map($f, $r->parsed);
                return r::make($r->unparsed, $mapped);
            }
        };
    }

    /**
     * @param callable(string):?r $head
     * @param array<int,callable(string):?r> $tail
     * @return callable(string):?r
     */
    public static function or(callable $head, callable ...$tail): callable
    {
        switch (count($tail)) {
            case 0:
                return $head;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = self::or(...$tail);
        }
        return function (string $input) use ($head, $tail): ?r {
            return $head($input) ?? $tail($input);
        };
    }

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
     * @param callable(string):?r $p
     * @return callable(string):?r
     */
    public static function repeat(callable $p): callable
    {
        return function (string $input) use ($p): r {
            $parsed = [];
            $result = $p($input);
            while (null !== $result) {
                $input = $result->unparsed;
                $parsed = array_merge($parsed, $result->parsed);
                $result = $p($input);
            }

            return r::make($input, $parsed);
        };
    }
}
