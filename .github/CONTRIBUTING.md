# Contributing

If you have a something problem. Please post to [Issues](https://github.com/getshifter/wp-cli-shifter/issues).

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
