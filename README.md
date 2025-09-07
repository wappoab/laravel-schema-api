# This is my package laravel-schema-api

[![Run Tests](https://github.com/wappoab/laravel-schema-api/actions/workflows/run-test.yml/badge.svg)](https://github.com/wappoab/laravel-schema-api/actions/workflows/run-test.yml)

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
