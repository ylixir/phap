<?php
declare(strict_types=1);
namespace Phap;

use Phap\Result as r;

final class Functions
{
    //convenience constants for passing functions to functions
    const alternatives = self::class . "::alternatives";
    const apply = self::class . "::apply";
    const binary = self::class . "::binary";
    const drop = self::class . "::drop";
    const end = self::class . "::end";
    const eol = self::class . "::eol";
    const fail = self::class . "::fail";
    const float = self::class . "::float";
    const fold = self::class . "::fold";
    const hex = self::class . "::hex";
    const int = self::class . "::int";
    const lit = self::class . "::lit";
    const map = self::class . "::map";
    const not = self::class . "::not";
    const octal = self::class . "::octal";
    const pop = self::class . "::pop";
    const repeat = self::class . "::repeat";
    const sequence = self::class . "::sequence";
    const spaces = self::class . "::spaces";
    const whitespace = self::class . "::whitespace";

    /**
     * @param callable(string):?r $head
     * @param callable(string):?r ...$tail
     * @return callable(string):?r
     */
    public static function alternatives(
        callable $head,
        callable ...$tail
    ): callable {
        switch (count($tail)) {
            case 0:
                return $head;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = self::alternatives(...$tail);
        }
        return function (string $input) use ($head, $tail): ?r {
            return $head($input) ?? $tail($input);
        };
    }

    /**
     * @template S
     * @template T
     * @param callable(T...):array<int,S> $f
     * @param callable(string):?r $p
     * @return callable(string):?r
     */
    public static function apply(callable $f, callable $p): callable
    {
        return function (string $in) use ($f, $p): ?r {
            $r = $p($in);
            if (null === $r) {
                return $r;
            } else {
                return r::make($r->unparsed, $f(...$r->parsed));
            }
        };
    }

    /**
     * @return callable(string):?r<int>
     */
    public static function binary(): callable
    {
        /**
         * @var callable(string):?r<string>
         */
        $digits = self::alternatives(self::lit("0"), self::lit("1"));

        $intString = self::sequence($digits, self::repeat($digits));

        $intVal = function (string $d, int $a): array {
            return [($a << 1) | (int) $d];
        };

        return self::fold($intVal, [0], $intString);
    }

    /**
     * @template T
     * @param callable(string):?r<T> $start
     * @param callable(string):?r<T> $end
     * @param callable(string):?r<T> $escape
     * @return callable(string):?r<T>
     */
    public static function block(
        callable $start,
        callable $end,
        callable $escape
    ): callable {
        $muncher = self::sequence(self::not($end), self::pop());
        $middle = self::alternatives($escape, $muncher);
        return self::sequence($start, self::repeat($middle), $end);
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
     * @return callable(string):?r
     */
    public static function end(): callable
    {
        return function (string $in): ?r {
            if ('' === $in) {
                return r::make('', []);
            } else {
                return null;
            }
        };
    }

    /**
     * @return callable(string):?r<string>
     */
    public static function eol(): callable
    {
        return self::alternatives(
            self::lit("\n"),
            self::lit("\r\n"),
            self::lit("\r")
        );
    }

    /**
     * @return callable(string):null
     */
    public static function fail(): callable
    {
        return function (string $s): ?r {
            return null;
        };
    }

    /**
     * @return callable(string):?r<float>
     */
    public static function float(): callable
    {
        //mantissa and fractional integer's may have leading zeros
        $zeros_integer = self::alternatives(
            self::sequence(
                self::drop(self::repeat(self::lit("0"))),
                self::int()
            ),
            self::sequence(
                self::int(),
                self::drop(self::repeat(self::lit("0")))
            )
        );

        $fractional_part = self::map(function (int $q): float {
            for ($d = 1; $d <= $q; $d *= 10);
            return $q / $d;
        }, $zeros_integer);
        $integer_part = self::map('floatval', self::int());

        // parse a non-scientific float into integer and decimal parts
        $parts = self::alternatives(
            self::sequence(
                $integer_part,
                self::drop(self::lit(".")),
                $fractional_part
            ),
            self::sequence($integer_part, self::drop(self::lit("."))),
            self::sequence(self::drop(self::lit(".")), $fractional_part)
        );
        $float = self::fold(
            /** @return array{0:float} */ function (float $p, float $i): array {
                return [$i + $p];
            },
            [0.0],
            $parts
        );

        $e = self::drop(self::alternatives(self::lit("e"), self::lit("E")));

        $negative_integer = self::map(function (int $i): int {
            return -$i;
        }, self::sequence(self::drop(self::lit("-")), $zeros_integer));
        $positive_integer = self::sequence(
            self::drop(self::lit("+")),
            $zeros_integer
        );
        $mantissa = self::map(function (int $i): float {
            return pow(10, $i);
        }, self::sequence(
            $e,
            self::alternatives(
                $zeros_integer,
                $negative_integer,
                $positive_integer
            )
        ));
        $scientific = self::fold(
            /** @return array{0:float} */ function (float $a, float $b): array {
                return [$a * $b];
            },
            [1],
            self::sequence(self::alternatives($float, $integer_part), $mantissa)
        );

        return self::alternatives($scientific, $float);
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
                /** @var array<int, mixed> */ $reduced = array_reduce(
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
    public static function hex(): callable
    {
        /**
         * @var array<int, callable(string):?r<string>>
         */
        $decLits = array_map(self::lit, range("0", "9"));
        /**
         * @var array<int, callable(string):?r<string>>
         */
        $hexLits = array_merge(
            array_map(self::lit, range("a", "f")),
            array_map(self::lit, range("A", "F"))
        );

        $dec = self::map('intval', self::alternatives(...$decLits));
        $hex = self::map(function (string $v): int {
            return 9 + (0xf & ord($v));
        }, self::alternatives(...$hexLits));

        $digits = self::alternatives($dec, $hex);

        $hexSequence = self::sequence($digits, self::repeat($digits));

        $hexVal = function (int $d, int $a): array {
            return [$a * 0x10 + $d];
        };

        return self::fold($hexVal, [0], $hexSequence);
    }

    /**
     * @return callable(string):?r<int>
     */
    public static function int(): callable
    {
        /**
         * @var array<int, callable(string):?r<string>>
         */
        $firstLits = array_map(self::lit, range("1", "9"));
        $zeroLit = self::lit("0");
        $firstDigits = self::alternatives(...$firstLits);
        $digits = self::alternatives($zeroLit, ...$firstLits);

        $intString = self::alternatives(
            self::sequence($firstDigits, self::repeat($digits)),
            $zeroLit
        );

        $intVal = function (string $d, int $a): array {
            return [$a * 10 + (int) $d];
        };

        return self::fold($intVal, [0], $intString);
    }

    /**
     * @return callable(string):?r<string>
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
                /** @var r<string> */
                $result = r::make($tail, [$head]);
                return $result;
            } else {
                return null;
            }
        };
    }

    /**
     * @template S
     * @template T
     * @param callable(S):T $f
     * @param callable(string):?r<S> $p
     * @return callable(string):?r<T>
     */
    public static function map(callable $f, callable $p): callable
    {
        return /**
             * @return ?r<T>
             */
            function (string $in) use ($f, $p): ?r {
                $r = $p($in);
                if (null === $r) {
                    return $r;
                } else {
                    /** @var array<int, mixed> */ $mapped = array_map(
                        $f,
                        $r->parsed
                    );
                    return r::make($r->unparsed, $mapped);
                }
            };
    }

    /**
     * @template T
     * @param callable(string):?r<T> $p
     * @return callable(string):?r<T>
     */
    public static function not(callable $p): callable
    {
        return function (string $s) use ($p): ?r {
            if ($p($s)) {
                return null;
            } else {
                return r::make($s, []);
            }
        };
    }

    /**
     * @return callable(string):?r<int>
     */
    public static function octal(): callable
    {
        /**
         * @var array<int, callable(string):?r<string>>
         */
        $digitLits = array_map(self::lit, range("0", "7"));
        $digits = self::alternatives(...$digitLits);

        $intString = self::sequence($digits, self::repeat($digits));

        $intVal = function (string $d, int $a): array {
            return [$a * 010 + (int) $d];
        };

        return self::fold($intVal, [0], $intString);
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

    /**
     * @return callable(string):?r<string>
     */
    public static function spaces(): callable
    {
        $space = self::alternatives(self::lit(" "), self::lit("\t"));

        return self::sequence($space, self::repeat($space));
    }

    /**
     * @return callable(string):?r<string>
     */
    public static function whitespace(): callable
    {
        $ws = self::alternatives(self::spaces(), self::eol());
        return self::sequence($ws, self::repeat($ws));
    }

    /**
     * @param callable(string):?r $head
     * @param array<int,callable(string):?r> $tail
     * @return callable(string):?r
     */
    public static function sequence(callable $head, callable ...$tail): callable
    {
        switch (count($tail)) {
            case 0:
                return $head;
            case 1:
                $tail = $tail[0];
                break;
            default:
                $tail = self::sequence(...$tail);
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
}
