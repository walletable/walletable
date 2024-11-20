<p align="center">
<a href="https://github.com/walletable/walletable"><img src="https://github.com/walletable/walletable/actions/workflows/tests.yml/badge.svg" alt="Github"></a>
<a href="https://packagist.org/packages/walletable/walletable"><img src="https://img.shields.io/packagist/dt/walletable/walletable" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/walletable/walletable"><img src="https://img.shields.io/packagist/v/walletable/walletable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/walletable/walletable"><img src="https://img.shields.io/packagist/l/walletable/walletable" alt="License"></a>
</p>

# Walletable

> A robust Laravel package for creating e-wallets to manage and analyze digital financial assets per entity in your Laravel application.
>
## Table of Contents

- Features
- Requirements
- Installation
- Configuration
- Basic Usage
- Advanced Usage
- Architecture
- Database Schema
- Events
- Exception Handling
- Testing
- Security
- Contributing
- License

## Features

- Multiple wallets per entity with polymorphic relations
- Multi-currency support with proper money handling
- Secure transaction processing with optimistic locking
- Flexible transaction actions system
- Event-driven architecture
- Support for both auto-increment and UUID primary keys
- Comprehensive exception handling
- Transaction history and balance tracking
- Detailed meta information for transactions
- Transaction reversal capabilities

## Requirements

- PHP 7.1+
- Laravel 7.0+ | 8.0+ | 9.0+ | 10.0+ | 11.0+
- PHP ext-intl extension

### Via Composer

```bash
composer require walletable/walletable
```

## Post-Installation

Run the installation command:

```bash
php artisan walletable:install
```

## This will

- Publish the configuration file to `config/walletable.php`
- Publish migration files to `database/migrations`
- Publish model files to `app/Models`
- Optionally configure UUID support
