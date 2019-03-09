---
layout: page
title: "API: Core"
---

## Bring it in

First, you must obviously tell PHP to use the new hotness.

```php
use Phap\Functions as p;
```

# What you get

Each parser combinator returns an object that you can call like a function. You can then either invoke a parse, or use the combinators fo combine it with other parsers. Invoking a parse is done by calling the object like it is a function and passing it the thing you want parsed.

If the parse fails then you get back a `null`.

If the parse succeeds, then you get back a `Phap\Result`. This result object has two read only properties: `parsed` and `unparsed`. The former is an array, the latter is a string.

## `drop`

This function discards the parsed data. This might be useful for dropping whitespace for example.

```php
// parse "foo bar" into ["foo", "bar"]
$parser = p::sequence(p::lit("foo"), p::drop(p::lit(" ")), p::lit("bar"));

$success = $parser("foo bar");
$fail = $parser("foobar");
```

## `end`

This will check to see if we are at the end of input. Success means there is nothing left to parse.

```php
$parser = p::end(p::lit("foo"));

$success = $parser("foo");
$failure = $parser("foobar");
```

## `fail`

Always fails.

```php
$parser = p::fail();

assert(null === $parser("foo"));
```

## `fold`

Similar to `array_reduce`, this function can be used to combine values. For example, you might want to turn the array `["1","2","3"]` into the integer `123`.

```php
$flower = p::lit("flower");
$flowers = p::sequence($flower, $flower);

$parser = p::fold(
    function (string $in, string ...$acc): array {
        return ["flowers"];
    },
    [],
    $flowers
);

assert(["flowers"] === $parser("flowerflower"));
```

## `lit`

Checks to see if the unparsed data starts with the *lit*eral.

```php
$parser = p::lit("foo");

$success = $parser("foobar");
$fail = $parser("bar");
```

## `map`

This is used to convert raw data to more useful types. For example you might wish to convert a string containing an integer into an actual integer.

```php
// convert a "truthy" string to boolean
$parser = p::map(function (string $s): bool {
    return true;
}, p::lit("yes"));

assert([true] == $parser("yes")->parsed);
```

## `not`

Fails if the given parser is successful. Succeeds if not.

```php
$parser = p::not(p::lit("foo"));

assert(null === $parser("foo"));
assert([] === $parser("bar")->parsed);
assert("bar" === $parser("bar")->unparsed);
```

## `or`

Tries a list of parsers in order until one succeeds.

```php
$parser = p::or(p::lit("foo"), p::lit("hello"));

$success = $parser("foobar");
$success = $parser("hello world");
```

## `pop`

Grab a single item off of the parser input.

```php
$parser = p::pop();

assert(["1"] === $parser("123"));
```

## `repeat`

Just keep trying the same parser until it fails.

```php
$parser = p::repeat(p::lit("1"));

assert(["1", "1", "1"] === $parser("111"));
```

## `sequence`

This command checks for a sequence of matches, returning success only if all children parsers also return success.

```php
// parse "foobar"
$parser = p::sequence(p::lit("foo"), p::lit("bar"));

$success = $parser("foobar");
$fail = $parser("zoobaz");
```
