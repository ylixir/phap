---
layout: page
title: "Tutorial: 1. Getting Started"
---

<!-- prettier-ignore -->
1. Table of Contents
{:toc}

# Setting up composer

Phap is published as a `composer` package. [You will need to install composer for these instructions to work.](https://getcomposer.org/doc/00-intro.md)

Then you will need to tell PHP to have composer manage loading dependencies for you. This is done by placing the following code in your core PHP file. Maybe `index.php`?

```php
require __DIR__ . '/vendor/autoload.php';
```

# Installing Phap

Once you have composer installed then you can install `phap` by typing the following into the command line:

```shell
composer install ylixir/phap
```

Now just add the following to any `.php` file where you wish to use `phap`

```php
use Phap\Functions as p;
```

**Note:** This allows you to more access `phap` by aliasing it to the letter `p`. This may or may not be your preference, but we will use this convention
for our documnetation.
