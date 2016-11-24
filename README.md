# wp shifter

[![Build Status](https://travis-ci.org/getshifter/wp-cli-shifter.svg?branch=master)](https://travis-ci.org/getshifter/wp-cli-shifter)

The Shifter is a serverless hosting solution for WordPress.

https://getshifter.io/

`wp shifter` is a WP-CLI command that enables you to import/export your WordPress site for the Shifter.

## Requires

* WP-CLI 0.23 or later

## Subcommands

### Upload an archive to the Shifter.

`wp shifter upload` will deploy your WordPress site to the Shifter.

```
$ wp shifter upload
Shifter Username: jack
Password (will be hidden):
Success: Logged in as jack
Creating an archive:   100% [=======================] 0:23 / 0:04Success: Created an
archive.
Success: 🍺 Archive uploaded successfully.
```

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

### Help

```bash
$ wp help shifter

NAME

  wp shifter

DESCRIPTION

  WP-CLI commands for the Shifter.

SYNOPSIS

  wp shifter <command>

SUBCOMMANDS

  archive      Create a .zip archive as a archive for the Shifter.
  extract      Extract the WordPress site from a .zip archive.
  upload       Upload an archive to the Shifter.
  version      Prints current version of the shifter/cli.
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
