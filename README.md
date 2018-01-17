# codeception-drupal

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Use Gherkin DSL steps based on Drupal 8 within Codeception. This work is mainly based on the excellent Behat Extension named drupal/drupal-extension. This module is based on Codeception 2.3 or higher.

## Structure

Within the `config` directory, there is an example of how to configure your Codeception suite.

```
bin/
config/
src/
tests/
vendor/
```


## Install

Via Composer

``` bash
$ composer require arocom/codeception-drupal
```

## Usage

Substitute `<Suite>` with your suite, it's normally in lower case.
The `working_directory` key within the DrupalDrush module is the absolute path of your drupal installation.

```yaml
actor: <Suite>Tester
modules:
    enabled:
        - WebDriver
        - Asserts
        - \Helper\<Suite>
        - \Codeception\Module\Context\MinkContext
        - \Codeception\Module\Context\AcceptanceContext
        - \Codeception\Module\Context\BatchContext
        - \Codeception\Module\Context\MarkupContext
        - \Codeception\Module\Context\DrushContext
        - DrupalDrush
    config:
        WebDriver:
            url: 'https://www.drupal.org'
            browser: chrome
            host: '127.0.0.1'
            port: 4444
            window_size: 1920x1080
        \Codeception\Module\Context\MinkContext:
            DI: 'WebDriver'
            PSR-7: '\GuzzleHttp\Client'
        \Codeception\Module\Context\AcceptanceContext:
            DI: 'WebDriver'
            PSR-7: '\GuzzleHttp\Client'
        \Codeception\Module\Context\BatchContext:
            DI: 'WebDriver'
            PSR-7: '\GuzzleHttp\Client'
        \Codeception\Module\Context\MarkupContext:
            DI: 'WebDriver'
            PSR-7: '\GuzzleHttp\Client'
        \Codeception\Module\Context\DrushContext:
            DI: 'WebDriver'
            PSR-7: '\GuzzleHttp\Client'
            Drush: 'DrupalDrush'
            DrushAlias: 'drush'
        DrupalDrush:
            working_directory: '/var/www/drupal' # Absolute path
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email :author_email instead of using the issue tracker.

## Credits

- [Michael A. Johnson Lucas][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/arocom/codeception-drupal.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/arocom/codeception-drupal/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/arocom/codeception-drupal.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/arocom/codeception-drupal.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/arocom/codeception-drupal.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/arocom/codeception-drupal
[link-travis]: https://travis-ci.org/arocom/codeception-drupal
[link-scrutinizer]: https://scrutinizer-ci.com/g/arocom/codeception-drupal/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/arocom/codeception-drupal
[link-downloads]: https://packagist.org/packages/arocom/codeception-drupal
[link-author]: https://github.com/arocom
[link-contributors]: ../../contributors