<?php
declare(strict_types=1);
namespace Phap;

/**
 * @template T
 * @property string $unparsed
 * @property array<int,T> $parsed
 * @psalm-seal-properties
 */
final class Result
{
    /** @var string */ private $unparsed;
    /** @var array<int, T> */ private $parsed;

    public function __get(string $prop)
    {
        return $this->$prop;
    }

    /**
     * @param array<int, T> $parsed
     */
    private function __construct(string $unparsed, array $parsed)
    {
        $this->unparsed = $unparsed;
        $this->parsed = $parsed;
    }

    /**
     * @param array<int, T> $parsed
     */
    public static function make(string $unparsed, array $parsed): self
    {
        return new self($unparsed, $parsed);
    }
}
