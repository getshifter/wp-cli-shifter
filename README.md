# wp shifter

[![Build Status](https://travis-ci.org/getshifter/wp-cli-shifter.svg?branch=master)](https://travis-ci.org/getshifter/wp-cli-shifter)

## Requires

* WP-CLI 0.23 or later

## Subcommands

Backup your WordPress files and database.

```shell
$ wp shifter backup [<file>]
```

Recovery from backup.

```shell
$ wp shifter recovery <file> [--delete]
```

## Installing via package command

```shell
$ wp package install shifter/cli:@stable
```

## Installing manually

```shell
$ mkdir -p ~/.wp-cli/commands && cd -
$ git clone git@github.com:getshifter/wp-cli-shifter.git
```

Add following into your `~/.wp-cli/config.yml`.

```yaml
require:
  - commands/wp-cli-shifter/cli.php
```

## Automated testing

Setup:

```shell
$ composer install
$ bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
$ WP_CLI_BIN_DIR=/tmp/wp-cli-phar bash bin/install-package-tests.sh
```

Then run tests:

```shell
$ phpunit && WP_CLI_BIN_DIR=/tmp/wp-cli-phar ./vendor/bin/behat
```
