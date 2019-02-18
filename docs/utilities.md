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
```
