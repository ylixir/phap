---
layout: page
title: "API: Extra"
---

## Bring it in

Please visit the `core` documentation first to see how to use the library. This page provides documentation on some handy quality of life functions.

## `binary`

This parses a sequence of binary digits converting them to an `int`.

#### OOP and FP

```php
$parser = p::binary();

assert([0b100] === $parser("100")->parsed);
assert([0] === $parser("0")->parsed);

//doesn't include an end condition
assert('a' === $parser("101a")->unparsed);

assert(null === $parser("")->parsed);
//doesn't handle signs
assert(null === $parser("-100")->parsed);
```

## `block`

This is a block text parser. You'll want to use this for things like string and comment parsing.

```php
$quote = p::lit('"');
$escquote = p::lit('""');
$parser = p::block($quote, $quote, $escquote);
assert(['"', '1', '""', '2', '"'] === $parser('"1""2"')->parsed);

$cstart = p::lit("/*");
$cend = p::lit("*/");
$parser = p::block($cstart, $cend, p::fail());
assert(["/*", "/", "*", "a", "*/"] === $parser("/*/*a*/")->parsed);
```

#### OOP and FP

```php
$parser = p::binary();

assert([0b100] === $parser("100")->parsed);
assert([0] === $parser("0")->parsed);

//doesn't include an end condition
assert('a' === $parser("101a")->unparsed);

assert(null === $parser("")->parsed);
//doesn't handle signs
assert(null === $parser("-100")->parsed);
```

## `eol`

This parses an end of line. This might be dos, unix, or mac encoding.

#### OOP and FP

```php
$parser = p::eol();

assert(["\n"] === $parser("\n")->parsed);
assert(["\r\n"] === $parser("\r\n")->parsed);
assert(["\r"] === $parser("\r")->parsed);

//only parses one at a time. doesn't munch like spaces
assert(["\n"] === $parser("\n\r")->parsed);
assert("\r" === $parser("\n\r")->unparsed);

assert(null === $parser("")->parsed);
```

## `float`

This parses a series of decimal digits, periods, and the letter `e`, returning a `float`.

#### OOP and FP

```php
$parser = p::float();

assert([10.0] === $parser("10.")->parsed);
assert([1.0] === $parser("1.0")->parsed);
assert([0.1] === $parser(".1")->parsed);

assert([100.0] === $parser("10E1")->parsed);
assert([1.0] === $parser("10e-001")->parsed);
assert([100.0] === $parser("10e+1")->parsed);

assert([100.0] === $parser("10.e001")->parsed);
assert([0.1] === $parser("1.00E-1")->parsed);
assert([1.0] === $parser(".1E+001")->parsed);

assert([1.0] === $parser("1e00")->parsed);

assert('a' === $parser("1.a")->unparsed);

assert(null === $parser("123"));
assert(null === $parser(""));
```

## `hex`

This parses a sequence of hexadecimal digits converting them to an `int`.

#### OOP and FP

```php
$parser = p::hex();

assert([0x1a] === $parser("1a")->parsed);
assert([0xf] === $parser("F")->parsed);
assert([0] === $parser("0")->parsed);

//doesn't include an end condition or prefixes
assert([0] === $parser("0xa")->parsed);
assert("xa" === $parser("0xa")->unparsed);

assert(null === $parser("")->parsed);
//doesn't handle signs
assert(null === $parser("-123")->parsed);
```

## `int`

This parses a sequence of decimal digits converting them to an `int`.

#### OOP and FP

```php
$parser = p::int();

assert([123] === $parser("123")->parsed);
assert([0] === $parser("0")->parsed);

//doesn't include an end condition
assert('a' === $parser("123a")->unparsed);

assert(null === $parser("")->parsed);
//doesn't handle signs
assert(null === $parser("-123")->parsed);
//doesn't allow accidental octal collisions
assert(null === $parser("00")->parsed);
```

## `octal`

This parses a sequence of octal digits converting them to an `int`.

#### OOP and FP

```php
$parser = p::octal();

assert([0123] === $parser("123")->parsed);
assert([0] === $parser("0")->parsed);

//doesn't include an end condition
assert('a' === $parser("123a")->unparsed);

//doesn't handle signs
assert(null === $parser("-123")->parsed);

assert(null === $parser("")->parsed);
```

## `spaces`

This parses a sequence of spaces and tabs.

#### OOP and FP

```php
$parser = p::spaces();

assert([" ", "\t"] === $parser(" \t")->parsed);

assert(null === $parser("")->parsed);
```

## `whitespace`

This parses a sequence of spaces, tabs and newlines.

#### OOP and FP

```php
$parser = p::whitespace();

assert([" ", "\t", "\r\n"] === $parser(" \t\r\n")->parsed);

assert(null === $parser("")->parsed);
```
