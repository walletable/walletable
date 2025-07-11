<?php

namespace Walletable\Tests;

use Walletable\Tests\TestBench;
use Walletable\Models\Transaction;
use Walletable\Internals\Actions\ActionData;
use Walletable\Money\Money;

class BatchTransactionTest extends TestBench
{
    public function testCanPerformBatchCreditTransactions()
    {
        $wallet = $this->createWallet();
        $initialBalance = $wallet->amount;

        $transactions = [
            [
                'amount' => 1000,
                'data' => new ActionData('test'),
                'remarks' => 'Test credit 1',
                'meta' => ['type' => 'test']
            ],
            [
                'amount' => 2000,
                'data' => new ActionData('test'),
                'remarks' => 'Test credit 2',
            ]
        ];

        $results = $wallet->batchCredit($transactions);
        
        $this->assertCount(2, $results);
        $this->assertEquals($initialBalance + 3000, $wallet->fresh()->amount);
        $this->assertInstanceOf(Transaction::class, $results[0]);
        $this->assertEquals(1000, $results[0]->amount);
        $this->assertEquals(2000, $results[1]->amount);
    }

    public function testCanPerformBatchDebitTransactions()
    {
        $wallet = $this->createWallet();
        $wallet->action('credit_debit')->credit(5000, new ActionData('test'), 'Initial balance');
        $initialBalance = $wallet->amount;

        $transactions = [
            [
                'amount' => 1000,
                'data' => new ActionData('test'),
                'remarks' => 'Test debit 1',
                'meta' => ['type' => 'test']
            ],
            [
                'amount' => 2000,
                'data' => new ActionData('test'),
                'remarks' => 'Test debit 2',
            ]
        ];

        $results = $wallet->batchDebit($transactions);
        
        $this->assertCount(2, $results);
        $this->assertEquals($initialBalance - 3000, $wallet->fresh()->amount);
        $this->assertInstanceOf(Transaction::class, $results[0]);
        $this->assertEquals(-1000, $results[0]->amount);
        $this->assertEquals(-2000, $results[1]->amount);
    }

    public function testBatchTransactionIsAtomic()
    {
        $wallet = $this->createWallet();
        $wallet->action('credit_debit')->credit(5000, new ActionData('test'), 'Initial balance');
        $initialBalance = $wallet->amount;

        try {
            $wallet->batchDebit([
                [
                    'amount' => 1000,
                    'data' => new ActionData('test'),
                    'remarks' => 'Test debit 1',
                ],
                [
                    'amount' => 10000, // This should fail due to insufficient balance
                    'data' => new ActionData('test'),
                    'remarks' => 'Test debit 2',
                ]
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Verify that no transactions were committed
            $this->assertEquals($initialBalance, $wallet->fresh()->amount);
        }
    }
}
