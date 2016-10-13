# wp shifter

[![Build Status](https://travis-ci.org/getshifter/wp-cli-shifter.svg?branch=master)](https://travis-ci.org/getshifter/wp-cli-shifter)

The Shifter is a serverless hosting solution for WordPress.

https://getshifter.io/

`wp shifter` is a WP-CLI command that enables you to import/export your WordPress site for the Shifter.

## Requires

* WP-CLI 0.23 or later

## Subcommands

### Backup your WordPress files and database.

```bash
$ wp shifter archive [<file>] [--exclude=<files>]
```

You can exclude `wp-config.php`.

```
$ wp shifter archive /path/to/archive.zip --exclude=wp-config.php
```

### Extract from backup.

```bash
$ wp shifter extract <file> [--delete] [--exclude=<files>]
```

If you add `--delete` option, this command will remove all files before extracting.

```bash
$ wp shifter extract /path/to/archive.zip --delete
```

You can exclude specific files from archive.

```bash
$ wp shifter extract /path/to/archive.zip --exclude=wp-config.php
```

## Installing via package command

```bash
$ wp package install shifter/cli:@stable
```

## Installing manually

```bash
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

```bash
$ composer install
$ bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
$ WP_CLI_BIN_DIR=/tmp/wp-cli-phar bash bin/install-package-tests.sh
```

Then run tests:

```bash
$ phpunit && WP_CLI_BIN_DIR=/tmp/wp-cli-phar ./vendor/bin/behat
```
