# wp shifter

[![Build Status](https://travis-ci.org/getshifter/wp-cli-shifter.svg?branch=master)](https://travis-ci.org/getshifter/wp-cli-shifter)

## Requires

* WP-CLI 0.23 or later

## Install

```
$ wp package install shifter/wp-cli-shifter:@stable
```

## Examples

See help:

```
$ wp help shifter
```

Create a .zip archive for the Shifter.

```
$ wp shifter archive
```

## How to contribute

### Clone this repository

```
$ git clone git@github.com:megumiteam/wp-cli-shifter.git
```

### Manually activate

Add following into your `~/.wp-cli/config.yml`.

```
require:
  - path/to/cli.php
```

### Automated testing

```
$ npm run setup
$ npm test
```
