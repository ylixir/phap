<?php
declare(strict_types=1);
namespace Phap;

/**
 * @property string $unparsed
 * @property array<int,mixed> $parsed
 * @psalm-seal-properties
 */
final class Result
{
    /** @var string */ private $unparsed;
    /** @var array */ private $parsed;

    public function __get(string $prop)
    {
        return $this->$prop;
    }
    private function __construct(string $unparsed, array $parsed)
    {
        $this->unparsed = $unparsed;
        $this->parsed = $parsed;
    }

    public static function make(string $unparsed, array $parsed): self
    {
        return new self($unparsed, $parsed);
    }
}
