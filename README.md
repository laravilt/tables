![Tables](./arts/screenshot.jpg)

# Tables Plugin for Laravilt

[![Latest Stable Version](https://poser.pugx.org/laravilt/tables/version.svg)](https://packagist.org/packages/laravilt/tables)
[![License](https://poser.pugx.org/laravilt/tables/license.svg)](https://packagist.org/packages/laravilt/tables)
[![Downloads](https://poser.pugx.org/laravilt/tables/d/total.svg)](https://packagist.org/packages/laravilt/tables)
[![Dependabot Updates](https://github.com/laravilt/tables/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/laravilt/tables/actions/workflows/dependabot/dependabot-updates)
[![PHP Code Styling](https://github.com/laravilt/tables/actions/workflows/fix-php-code-styling.yml/badge.svg)](https://github.com/laravilt/tables/actions/workflows/fix-php-code-styling.yml)
[![Tests](https://github.com/laravilt/tables/actions/workflows/tests.yml/badge.svg)](https://github.com/laravilt/tables/actions/workflows/tests.yml)

plugin for Laravilt

## Installation

You can install the plugin via composer:

```bash
composer require laravilt/tables
```

The package will automatically register its service provider which handles all Laravel-specific functionality (views, migrations, config, etc.).

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="tables-config"
```

## Assets

Publish the plugin assets:

```bash
php artisan vendor:publish --tag="tables-assets"
```

## Testing

```bash
composer test
```

## Code Style

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
