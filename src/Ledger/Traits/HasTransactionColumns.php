<?php

namespace Walletable\Ledger\Traits;

use Walletable\Exceptions\InvalidArgumentException;
use Walletable\Models\Transaction;

/**
 * Registry of the extra transaction columns an application has declared.
 *
 * The package ships no opinion on what those columns are. The consuming
 * application writes its own migration and declares the column names, usually
 * from a service provider:
 *
 *     Walletable::extendTransaction(['channel', 'gateway_reference']);
 *
 * Only declared columns are written by PostTransaction, so a typo on a draft
 * fails loudly instead of silently disappearing.
 */
trait HasTransactionColumns
{
    /**
     * Extra transaction columns declared by the application.
     *
     * @var array<int,string>
     */
    protected $transactionColumns = [];

    /**
     * Declare extra columns that drafts and actions may write on the
     * transaction row. Additive; safe to call more than once.
     *
     * @param array<int,string>|string $columns
     */
    public function extendTransaction($columns): self
    {
        foreach ((array)$columns as $column) {
            if (!is_string($column) || $column === '') {
                throw new InvalidArgumentException('A transaction column name must be a non-empty string.');
            }

            if (in_array($column, Transaction::RESERVED_COLUMNS, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Column "%s" is managed by Walletable and cannot be extended.',
                    $column
                ));
            }

            if (!in_array($column, $this->transactionColumns, true)) {
                $this->transactionColumns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Every extra transaction column the application has declared.
     *
     * @return array<int,string>
     */
    public function transactionColumns(): array
    {
        return $this->transactionColumns;
    }

    public function allowsTransactionColumn(string $column): bool
    {
        return in_array($column, $this->transactionColumns, true);
    }

    /**
     * Drop every declared column. Mainly useful between tests.
     */
    public function flushTransactionColumns(): void
    {
        $this->transactionColumns = [];
    }
}
