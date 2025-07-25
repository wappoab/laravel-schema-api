# This is my package laravel-schema-api

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wappo/laravel-schema-api.svg?style=flat-square)](https://packagist.org/packages/wappo/laravel-schema-api)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wappo/laravel-schema-api/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wappo/laravel-schema-api/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wappo/laravel-schema-api/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wappo/laravel-schema-api/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wappo/laravel-schema-api.svg?style=flat-square)](https://packagist.org/packages/wappo/laravel-schema-api)

## Installation

You can install the package via composer:

```bash
composer require wappo/laravel-schema-api
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-schema-api-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-schema-api-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-schema-api-views"
```

## Usage

Just install the package and you models will be exposed through http.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Johan Östling](https://github.com/kjostling)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
