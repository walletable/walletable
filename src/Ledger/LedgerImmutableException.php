<?php

namespace Walletable\Ledger;

use RuntimeException;

/**
 * Thrown when callers attempt to modify or delete an append-only
 * ledger record (postings, and immutable fields on journal entries).
 */
class LedgerImmutableException extends RuntimeException
{
}
