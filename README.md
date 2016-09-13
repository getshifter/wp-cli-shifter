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
```

Create a .zip backup.

```
$ wp shifter backup
```

Recovery from a .zip.

```
$ wp shifter recovery path/to/archive.zip
```

## How to contribute

```
$ git clone git@github.com:megumiteam/wp-cli-shifter.git
```

Add following into your `~/.wp-cli/config.yml`.

```
require:
  - path/to/cli.php
```

Run tests.

```
$ npm run setup
$ npm test
```
