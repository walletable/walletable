<?php

namespace Walletable\Exceptions;

use RuntimeException;

/**
 * Base class for every domain exception the package throws.
 *
 * Extends RuntimeException rather than AssertionError so that the ubiquitous
 * `catch (\Exception $e)` block actually catches these. AssertionError extends
 * Error, which sits outside the Exception hierarchy entirely.
 */
class WalletableException extends RuntimeException
{
}
