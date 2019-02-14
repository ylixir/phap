# Development environment

This document describes tooling for contributers to this library. If you only wish to use this software, then needn't worry about any of this. Simply install the `ylixir/phap` package with `composer`.

We provide a dedicated development environment for contritutors. Manages localized versions of `php` and `composer`, etc. that will not interfere with anything you may have installed on your system. You may use the provided `./go` script to interact with the development environment.

## Supported platforms

Any platform that `nix` runs under will work. This includes

-   Apple Macintosh OS X
-   Linux
-   Microsoft Windows Subsystem for Linux (WSL)

If you are a windows user, you can find [information on installing WSL here](https://github.com/michaeltreat/Windows-Subsystem-For-Linux-Setup-Guide)

## Requirements

You the environment needs only `nix`. If you do not have `nix` then it can attempt to install it for you. The following software needs to be present in order to download and install `nix`.

-   `curl`
-   `bzip2`
-   `git`
-   `bash`

## Getting started

To get setup run `./go init` from the command line. This will install `nix` and then create a local environment which includes all software needed to support development (except a text editor!).

## Commands

Use the command with the provided script by running `./go <command>`
|command|description|
|---|---|
|debug|Drops us into a shell prompt with `php` configured with `xdebug`.|
|run|Drops us into a shell prompt with `xdebug` disabled.|
|init|Set's up and installs the dependencies for the project.|
|version|Get the current version of the library.|
|tagged|Check if the currenct version has a release tag yet.|
|tag|Create a new version tag.|
|format-verify|Make sure that all code has been properly formatted.|
|format|Automatically reformat all source code.|
|strict-types|Make sure all php source files have `strict_types` turned on.|
|lint|Check source code for errors with `psalm`|
|test-debug|Run unit tests with `xdebug` enabled (slow).|
|test|Run unit tests with no debugging (faster).|
|check|Run this before opening a pull request. Combines `format`, `test`, `lint`, `strict-types`|
