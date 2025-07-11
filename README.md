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
- Architecture (Docs WIP)
- Database Schema (Docs WIP)
- Events (Docs WIP)
- Exception Handling (Docs WIP)
- Testing (Docs WIP)
- Security (Docs WIP)
- Contributing (Docs WIP)
- License (Docs WIP)

## Features

- Multiple wallets per entity with polymorphic relations
- Multi-currency support with proper money handling
- Secure transaction processing with optimistic locking
- Flexible transaction actions system
- Event-driven architecture
- Support for both auto-increment and UUID primary keys
- Comprehensive exception handling
- Transaction status tracking and management
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

## Configuration

### Basic Configuration

```php
// config/walletable.php
return [
    'locker' => env('WALLETABLE_LOCKER', 'optimistic'),
    'models' => [
        'wallet' => \App\Models\Wallet::class,
        'transaction' => \App\Models\Transaction::class,
    ],
    'model_uuids' => false,
];
```

## Basic Usage

### Making a Model Walletable

```php
use Walletable\Contracts\Walletable;

class User extends Model implements Walletable
{
    public function getOwnerName()
    {
        return $this->name;
    }

    public function getOwnerEmail()
    {
        return $this->email;
    }

    public function getOwnerID()
    {
        return $this->id;
    }

    public function getOwnerMorphName()
    {
        return 'user';
    }
}
```

### Creating a Wallet

```php
use Walletable\Facades\Walletable;

$wallet = Walletable::create(
    $user,         // Walletable entity
    'Main Wallet', // Label
    'main',        // Tag
    'USD'          // Currency
);
```

### Basic Transactions

```php 
// Credit transaction
$wallet->action('credit_debit')->credit(
    1000,                      // Amount
    new ActionData('payment'), // Transaction data
    'Payment received'         // Remarks
);

// Debit transaction
$wallet->action('credit_debit')->debit(
    Money::USD(500),          // Amount as Money object
    new ActionData('withdrawal'),
    'ATM withdrawal'
);
```

### Checking Balances

```php
// Get raw amount
$balance = $wallet->amount;

// Get Money object
$money = $wallet->money();

// Formatted balance
$formatted = $wallet->money()->format(); // "$100.00"

// Check sufficient balance
$isEnough = $wallet->money()->greaterThanOrEqual(
    Money::USD(1000)
);
```

### Transaction Status Management

```php
// Create a transaction with pending status
$wallet->action('credit_debit')->credit(
    1000,
    new ActionData('payment'),
    'Payment received',
    ['status' => 'pending'] // Initial status
);

// Update transaction status
$transaction->updateStatus('completed');

// Check transaction status
if ($transaction->status === 'completed') {
    // Process completed transaction
}

// List transactions by status
$pendingTransactions = $wallet->transactions()
    ->where('status', 'pending')
    ->get();

// Available statuses:
// - pending: Transaction is awaiting processing or confirmation
// - completed: Transaction has been successfully processed
// - failed: Transaction has failed
// - reversed: Transaction has been reversed
// - cancelled: Transaction was cancelled before processing
```

### Advanced Usage

## Custom Transaction Actions

```php
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;
use Walletable\Internals\Actions\ActionData;

class PaymentAction implements ActionInterface
{
    public function apply(Transaction $transaction, ActionData $data)
    {
        $transaction->meta = [
            'payment_type' => $data->argument(0)->getValue(),
            'reference' => $data->argument(1)->getValue()
        ];
    }

    public function title(Transaction $transaction)
    {
        return "Payment via {$transaction->meta['payment_type']}";
    }

    public function image(Transaction $transaction)
    {
        return "/images/payment-icon.png";
    }

    public function supportDebit(): bool
    {
        return true;
    }

    public function supportCredit(): bool
    {
        return true;
    }

    public function reversable(Transaction $transaction): bool
    {
        return true;
    }

    public function reverse(Transaction $transaction, Transaction $new): ActionInterface
    {
        $new->meta = [
            'original_transaction_id' => $transaction->id,
            'reversal_reason' => 'customer_request'
        ];
        return $this;
    }

    public function methodResource(Transaction $transaction)
    {
        return $transaction->method;
    }
}

// Register the action
Walletable::action('payment', PaymentAction::class);
```

