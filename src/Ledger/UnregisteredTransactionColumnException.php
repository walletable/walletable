<?php

namespace Walletable\Ledger;

use RuntimeException;

/**
 * Thrown when a draft carries an extra transaction column the application
 * never declared through Walletable::extendTransaction().
 */
class UnregisteredTransactionColumnException extends RuntimeException
{
}
