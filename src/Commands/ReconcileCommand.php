<?php

namespace Walletable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify the three invariants that protect the ledger:
 *
 *   1. Per-wallet:      wallets.amount == SUM(signed posting amounts)
 *   2. Per-transaction: every posted transaction's debits == its credits
 *   3. Per-currency:    SUM(wallets.amount) GROUP BY currency == 0
 *
 * Exits non-zero on any failure. Designed to be run as a nightly cron and
 * after destructive operations.
 */
class ReconcileCommand extends Command
{
    protected $signature = 'walletable:reconcile {--currency= : Limit the global check to a single currency}';

    protected $description = 'Verify ledger invariants between wallets and postings.';

    public function handle(): int
    {
        $walletTable = (new (config('walletable.models.wallet')))->getTable();
        $postingTable = (new (config('walletable.models.posting')))->getTable();
        $transactionTable = (new (config('walletable.models.transaction')))->getTable();

        $failures = 0;

        $failures += $this->checkPerWalletProjection($walletTable, $postingTable, $transactionTable);
        $failures += $this->checkPerTransactionBalance($postingTable, $transactionTable);
        $failures += $this->checkPerCurrencySum($walletTable);

        if ($failures > 0) {
            $this->error(sprintf('Reconciliation failed: %d invariant violation(s).', $failures));
            return self::FAILURE;
        }

        $this->info('Reconciliation passed. All ledger invariants hold.');
        return self::SUCCESS;
    }

    protected function checkPerWalletProjection(string $walletTable, string $postingTable, string $transactionTable): int
    {
        $this->line('<info>[1/3]</info> Per-wallet projection check');

        $rows = DB::table($walletTable . ' as w')
            ->leftJoin($postingTable . ' as p', 'p.wallet_id', '=', 'w.id')
            ->leftJoin($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->where(function ($q) {
                $q->whereNull('t.id')->orWhere('t.status', 'posted');
            })
            ->groupBy('w.id', 'w.amount')
            ->selectRaw(
                "w.id as wallet_id, w.amount as recorded, " .
                "COALESCE(SUM(CASE p.direction WHEN 'C' THEN p.amount ELSE -p.amount END), 0) as derived"
            )
            ->get();

        $failures = 0;
        foreach ($rows as $row) {
            if ((int)$row->recorded !== (int)$row->derived) {
                $this->warn(sprintf(
                    '  wallet=%s recorded=%d derived=%d delta=%d',
                    $row->wallet_id,
                    $row->recorded,
                    $row->derived,
                    $row->recorded - $row->derived
                ));
                $failures++;
            }
        }
        if ($failures === 0) {
            $this->line('  OK');
        }
        return $failures;
    }

    protected function checkPerTransactionBalance(string $postingTable, string $transactionTable): int
    {
        $this->line('<info>[2/3]</info> Per-transaction debit/credit balance check');

        $rows = DB::table($postingTable . ' as p')
            ->join($transactionTable . ' as t', 't.id', '=', 'p.transaction_id')
            ->where('t.status', 'posted')
            ->groupBy('p.transaction_id')
            ->selectRaw(
                "p.transaction_id, " .
                "SUM(CASE WHEN p.direction = 'C' THEN p.amount ELSE 0 END) as credit_sum, " .
                "SUM(CASE WHEN p.direction = 'D' THEN p.amount ELSE 0 END) as debit_sum"
            )
            ->get();

        $failures = 0;
        foreach ($rows as $row) {
            if ((int)$row->credit_sum !== (int)$row->debit_sum) {
                $this->warn(sprintf(
                    '  transaction=%s credit=%d debit=%d',
                    $row->transaction_id,
                    $row->credit_sum,
                    $row->debit_sum
                ));
                $failures++;
            }
        }
        if ($failures === 0) {
            $this->line('  OK');
        }
        return $failures;
    }

    protected function checkPerCurrencySum(string $walletTable): int
    {
        $this->line('<info>[3/3]</info> Per-currency global sum-to-zero check');

        $query = DB::table($walletTable)
            ->groupBy('currency')
            ->selectRaw('currency, SUM(amount) as total');

        if ($currency = $this->option('currency')) {
            $query->where('currency', $currency);
        }

        $rows = $query->get();

        $failures = 0;
        foreach ($rows as $row) {
            if ((int)$row->total !== 0) {
                $this->warn(sprintf(
                    '  currency=%s sum=%d (expected 0)',
                    $row->currency,
                    $row->total
                ));
                $failures++;
            }
        }
        if ($failures === 0) {
            $this->line('  OK');
        }
        return $failures;
    }
}
