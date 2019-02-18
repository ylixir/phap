# Bring it in

We provide two equivalent APIs. One "functional programming" (FP) and one "object oriented programming" (OOP). Neither is better. we have tried to make the object API have natural language patterns, while the functional API should have more consistent (orthogonal) patterns.

Choose your own adventure

#### OOP

```php
use Phap\Oop as p;
```

#### FP

```php
use Phap\Functions as p;
```

# What you get

Each parser combinator returns an object that you can call like a function. You can then either invoke a parse, or use the combinators fo combine it with other parsers. Invoking a parse is done by calling the object like it is a function and passing it the thing you want parsed.

If the parse fails then you get back a `null`.

If the parse succeeds, then you get back a `Phap\Result`. This result object has two read only properties: `parsed` and `unparsed`. The former is an array, the latter is a string.

## `and`

This command checks for a sequence of matches, returning success only if all children parsers also return success.

#### OOP

```php
// parse "foobar"
$parser = p::lit("foo")->and(p::lit("bar"));

$success = $parser("foobar");
$fail = $parser("zoobaz");
```

#### FP

```php
// parse "foobar"
$parser = p::and(p::lit("foo"), p::lit("bar"));

$success = $parser("foobar");
$fail = $parser("zoobaz");
```

## `drop`

This functional discards the parsed data. This might be useful for dropping whitespace for example.

#### OOP

```php
// parse "foo bar" into ["foo", "bar"]
$parser = p::lit("foo")->and(p::lit(" ")->drop(), p::lit("bar"));

$success = $parser("foo bar");
$fail = $parser("foobar");
```

#### FP

```php
// parse "foo bar" into ["foo", "bar"]
$parser = p::and(p::lit("foo"), p::drop(p::lit(" ")), p::lit("bar"));

$success = $parser("foo bar");
$fail = $parser("foobar");
```

### `end`

This will check to see if we are at the end of input. Success means there is nothing left to parse.

#### OOP

```php
$parser = p::lit("foo")->end();

$success = $parser("foo");
$failure = $parser("foobar");
```

#### FP

```php
$parser = p::end(p::lit("foo"));

$success = $parser("foo");
$failure = $parser("foobar");
```

## `fold`

Similar to `array_reduce`, this function can be used to combine values. For example, you might want to turn the array `["1","2","3"]` into the integer `123`.

#### OOP

```php
$flower = p::lit("flower");
$flowers = $flower->and($flower);

$parser = $flowers->fold(function (string $in, string ...$acc): array {
    return ["flowers"];
}, []);

assert(["flowers"] === $parser("flowerflower"));
```

#### FP

```php
$flower = p::lit("flower");
$flowers = p::and($flower, $flower);

$parser = p::fold(
    function (string $in, string ...$acc): array {
        return ["flowers"];
    },
    [],
    $flowers
);

assert(["flowers"] === $parser("flowerflower"));
```

### `lit`

Checks to see if the unparsed data starts with the *lit*eral.

#### OOP and FP

```php
$parser = p::lit("foo");

$success = $parser("foobar");
$fail = $parser("bar");
```

### `map`

This is used to convert raw data to more useful types. For example you might wish to convert a string containing an integer into an actual integer.

#### OOP

```php
// convert a "truthy" string to boolean
$parser = p::lit("yes")->map(function (string $s): bool {
    return true;
});

assert([true] == $parser("yes")->parsed);
```

#### FP

```php
// convert a "truthy" string to boolean
$parser = p::map(function (string $s): bool {
    return true;
}, p::lit("yes"));

assert([true] == $parser("yes")->parsed);
```

### `or`

Tries a list of parsers in order until one succeeds.

#### OOP

```php
$parser = p::lit("foo")->or(p::lit("hello"));

$success = $parser("foobar");
$success = $parser("hello world");
```

#### FP

```php
$parser = p::or(p::lit("foo"), p::lit("hello"));

$success = $parser("foobar");
$success = $parser("hello world");
```

## `pop`

Grab a single item off of the parser input.

#### OOP and FP

```php
$parser = p::pop();

assert(["1"] === $parser("123"));
```

## `repeat`

Just keep trying the same parser until it fails.

#### OOP

```php
$parser = p::lit("1")->repeat();

assert(["1", "1", "1"] === $parser("111"));
```

#### FP

```php
$parser = p::repeat(p::lit("1"));

assert(["1", "1", "1"] === $parser("111"));
```
