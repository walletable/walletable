<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\DB;

class ReconcileTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testReconcilerPassesAfterValidActivity()
    {
        $wallet = $this->createWallet(0);
        $wallet->credit(10000);
        $wallet->debit(3000);

        $this->artisan('walletable:reconcile')
            ->expectsOutput('Reconciliation passed. All ledger invariants hold.')
            ->assertExitCode(0);
    }

    public function testReconcilerDetectsTampering()
    {
        $wallet = $this->createWallet(0);
        $wallet->credit(1000);

        // Manually break the projection: add to a wallet without a posting.
        DB::table('wallets')->where('id', $wallet->id)->update(['amount' => 9999]);

        $this->artisan('walletable:reconcile')
            ->assertExitCode(1);
    }
}
