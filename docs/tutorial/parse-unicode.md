---
layout: page
title: "Tutorial: 1. Embed Unicode"
---

<!-- prettier-ignore -->
1. Table of Contents
{:toc}

# Our goal

Parsing a string into an array of characters isn't that useful. We want to transform parsed data into something useful.

In json you can easily embed unicode characters into a string like so: `"\u002f"`. Wouldn't it be neat if we could do this in php? In this tutorial, we will make a parser that can take the string `"hello\u002fworld"` and transform it into `"hello/world"`.

Okay [you can actually do this already](https://secure.php.net/manual/en/migration70.new-features.php#migration70.new-features.unicode-codepoint-escape-syntax), but this is still a good excercise to demonstrate a useful technique.

Also it's a building block for a full fledged json parser.

# Prerequisites

You should have already completed the steps in the [getting started section.](/tutorial/getting-started)

# Break it down
Parsing is no different from any problem with programming. We start with a big hard problem and break it into small easy to solve problems.

Once broken down into the smallest possible pieces, we write parsers for the easy small bits. Then we build it back up into the big hard thing we need.
In our case, a sequence of _four_ hexadecimal digits.
So we start with _one_ hexadecimal digit.

But any one hexadecimal digit can be any one of 22 possible digits. We don't wanf to write those out by hand. We will just let our good friend `array_map` generate them for me.

```php
$hexDigits = array_merg(
    array_map(p::lit, range('0','9')),
    array_map(p::lit, range('a','z')),
    array_map(p::lit, range('A','Z'))
);
```

Awesome! Now we have an array of 22 parsers that can each parse one digit. We need _one_ parser that can parse 22 digits. Let us combine our 22 into one.

# Build it back up
So let's see hex digit might be `0` or alternatively `1`, alternatively `2`...wait a minute!

```php
$hexDigit = p::alternatives(...$hexDigits);
```

Okay, now that we can parse one digit, we can do a _sequence_ of four digits.

```php
$unicode = p::sequence(
    $hexDigit,
    $hexDigit,
    $hexDigit,
    $hexDigit
);
```

# And now the meat
Now we have a parser `$unicode` that will turn a string of four hex digits into an array of four hex characters. The next thing we need is to turn those four hex characters into a string with one unicode codepoint.

The magic function is `apply`. This function makes a parser from combining an existing parser with a function that takes a list of parameters which are the already parsed elements. The function returns an array.

```php
$unicode = p::apply(
    /**
     * @param array{0:string,1:string,2:string,3:string} $digits
     * @return array{0:string}
     */
    function (string ...$digits): array {
        $ucs2 = join('', $digits);
        $utf8 = iconv('UCS-2', 'UTF-8', $ucs2);
        return [$utf8];
    },
    $unicode
);

assert("/" === $unicode("002f")->parsed);
```

# Finish it up
Okay, now we can put it together to parse our original string.

```php
```