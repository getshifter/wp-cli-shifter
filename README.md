# wp shifter

[![Build Status](https://travis-ci.org/getshifter/wp-cli-shifter.svg?branch=master)](https://travis-ci.org/getshifter/wp-cli-shifter)

## Requires

* WP-CLI 0.23 or later

## Install

```
$ wp package install shifter/cli:@stable
```

## Examples

See help:

```
$ wp help shifter

NAME

  wp shifter

DESCRIPTION

  WP-CLI commands for the Shifter.

SYNOPSIS

  wp shifter <command>

SUBCOMMANDS

  backup        Create a .zip archive as a backup for the Shifter.
  recovery      Recovery the WordPress site from a .zip archive.

```

## How to contribute

```
$ mkdir -p ~/.wp-cli/commands && cd -
$ git clone git@github.com:getshifter/wp-cli-shifter.git
```

Add following into your `~/.wp-cli/config.yml`.

```
require:
  - commands/wp-cli-shifter/cli.php
```

## Automated testing

Setup:

```
$ composer install
$ bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
$ WP_CLI_BIN_DIR=/tmp/wp-cli-phar bash bin/install-package-tests.sh
```

Then run tests:

```
$ phpunit && WP_CLI_BIN_DIR=/tmp/wp-cli-phar ./vendor/bin/behat
```
