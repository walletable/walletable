<?php

namespace Walletable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Walletable\Events\TransactionConfirmed;
use Walletable\Events\TransactionPosted;
use Walletable\Exceptions\IdempotencyConflictException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Walletable as WalletableFacade;
use Walletable\Internals\Actions\Action;
use Walletable\Internals\Actions\ActionData;
use Walletable\Ledger\PostTransaction;
use Walletable\Ledger\TransactionDraft;
use Walletable\Models\HouseAccount;
use Walletable\Models\Posting;
use Walletable\Models\Transaction as BaseTransaction;
use Walletable\Money\Money;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Walletable;
use Walletable\Transaction\TransferAction;

/**
 * A retried credit, debit or transfer must move money exactly once. These cover
 * the replay path, the concurrent-insert path, and the refusal to replay when a
 * key is reused for materially different money.
 */
class IdempotencyTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testRepeatedCreditUnderOneKeyPostsOnce()
    {
        $wallet = $this->createWallet(0);

        $first = $wallet->credit(5000, 'Topup', 'webhook', 'evt_1');
        $second = $wallet->credit(5000, 'Topup', 'webhook', 'evt_1');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(5000, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame(2, Posting::query()->count());
    }

    public function testRepeatedDebitUnderOneKeyPostsOnce()
    {
        $wallet = $this->createWallet(5000);

        $first = $wallet->debit(2000, 'Withdrawal', 'webhook', 'evt_2');
        $second = $wallet->debit(2000, 'Withdrawal', 'webhook', 'evt_2');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(3000, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
    }

    public function testDistinctKeysPostSeparateTransactions()
    {
        $wallet = $this->createWallet(0);

        $first = $wallet->credit(5000, 'Topup', 'webhook', 'evt_1');
        $second = $wallet->credit(5000, 'Topup', 'webhook', 'evt_2');

        $this->assertNotSame($first->getKey(), $second->getKey());
        $this->assertSame(10000, $wallet->refresh()->amount->integer());
        $this->assertSame(2, Transaction::query()->count());
    }

    public function testOmittingTheKeyLeavesRetriesUnprotected()
    {
        $wallet = $this->createWallet(0);

        $wallet->credit(5000, 'Topup');
        $wallet->credit(5000, 'Topup');

        $this->assertSame(10000, $wallet->refresh()->amount->integer());
        $this->assertSame(2, Transaction::query()->count());
        $this->assertNull(Transaction::query()->first()->getRawOriginal('idempotency_key'));
    }

    public function testTransferIsIdempotent()
    {
        $sender = $this->createWallet(10000);
        $receiver = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Bisi',
            'email' => 'bisi@example.com',
        ]));

        $first = $sender->transfer($receiver, 4000, 'Rent', 'evt_transfer');
        $second = $sender->transfer($receiver, 4000, 'Rent', 'evt_transfer');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(6000, $sender->refresh()->amount->integer());
        $this->assertSame(4000, $receiver->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
    }

    public function testPendingTransactionIsIdempotent()
    {
        $wallet = $this->createWallet(0);

        $first = $wallet->unconfirmedCredit(5000, 'Topup', 'webhook', 'evt_pending');
        $second = $wallet->unconfirmedCredit(5000, 'Topup', 'webhook', 'evt_pending');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertTrue($second->isPending());
        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame(0, Posting::query()->count());
    }

    public function testReplayDoesNotRefirePostedEvent()
    {
        Event::fake([TransactionPosted::class]);

        $wallet = $this->createWallet(0);

        $wallet->credit(5000, 'Topup', 'webhook', 'evt_events');
        $wallet->credit(5000, 'Topup', 'webhook', 'evt_events');

        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testReusingAKeyForADifferentAmountIsRefused()
    {
        $wallet = $this->createWallet(10000);

        $original = $wallet->credit(5000, 'Topup', 'webhook', 'evt_conflict');

        try {
            $wallet->credit(6000, 'Topup', 'webhook', 'evt_conflict');
            $this->fail('Expected an IdempotencyConflictException.');
        } catch (IdempotencyConflictException $exception) {
            $this->assertSame($original->getKey(), $exception->getExisting()->getKey());
        }

        $this->assertSame(15000, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
    }

    public function testReusingAKeyForTheOppositeDirectionIsRefused()
    {
        $wallet = $this->createWallet(10000);

        $wallet->credit(5000, 'Topup', 'webhook', 'evt_direction');

        $this->expectException(IdempotencyConflictException::class);

        $wallet->debit(5000, 'Topup', 'webhook', 'evt_direction');
    }

    public function testTheActionApiTakesAnIdempotencyKey()
    {
        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestAction());

        $first = $action->credit(5000, new ActionData($wallet), 'Topup', 'evt_action');
        $second = $action->credit(5000, new ActionData($wallet), 'Topup', 'evt_action');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(5000, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame(2, Posting::query()->count());
    }

    /**
     * Same amount, same direction, different ActionData: the counterparty the
     * action resolved onto the leg differs, so this is a new intent wearing an
     * old key rather than a retry.
     */
    public function testReusingAKeyWithADifferentCounterpartyIsRefused()
    {
        $wallet = $this->createWallet(0);
        $bisi = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Bisi',
            'email' => 'bisi@example.com',
        ]));
        $tunde = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Tunde',
            'email' => 'tunde@example.com',
        ]));

        $action = new Action($wallet, new TransferAction());

        $action->credit(5000, new ActionData($bisi, $wallet), 'Rent', 'evt_counterparty');

        $this->expectException(IdempotencyConflictException::class);

        $action->credit(5000, new ActionData($tunde, $wallet), 'Rent', 'evt_counterparty');
    }

    /**
     * Columns the action stages on the transaction header count as payload too.
     */
    public function testReusingAKeyWithADifferentStagedColumnIsRefused()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('channel', 45)->nullable();
        });
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        $action->credit(5000, new ActionData($wallet, 'web'), 'Topup', 'evt_channel');

        $this->expectException(IdempotencyConflictException::class);

        $action->credit(5000, new ActionData($wallet, 'ussd'), 'Topup', 'evt_channel');
    }

    /**
     * A value that cannot be encoded has no distinct fingerprint, so it cannot
     * be told apart from any other unencodable value. Refusing is the only
     * answer that does not risk replaying somebody else's money.
     */
    public function testAStagedColumnThatCannotBeFingerprintedIsRefused()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('channel', 45)->nullable();
        });
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot fingerprint');

        $action->credit(5000, new ActionData($wallet, "\xB1\x31"), 'Topup', 'evt_binary');
    }

    /**
     * Only idempotent drafts are fingerprinted, so an unencodable value stays
     * usable for callers who never asked for de-duplication.
     */
    public function testAnUnencodableStagedColumnIsFineWithoutAKey()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('channel', 45)->nullable();
        });
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        $action->credit(5000, new ActionData($wallet, "\xB1\x31"), 'Topup');

        $this->assertSame(5000, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
    }

    public function testDifferingNarrationStillReplays()
    {
        $wallet = $this->createWallet(0);

        $first = $wallet->credit(5000, 'Topup', 'first wording', 'evt_narration');
        $second = $wallet->credit(5000, 'Topup', 'second wording', 'evt_narration');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(5000, $wallet->refresh()->amount->integer());
    }

    public function testConfirmingTwiceWritesThePostingsOnce()
    {
        Event::fake([TransactionConfirmed::class, TransactionPosted::class]);

        $wallet = $this->createWallet(0);
        $pending = $wallet->unconfirmedCredit(2000);

        $confirmed = $wallet->confirm($pending->refresh());
        $again = $wallet->confirm($confirmed->refresh());

        $this->assertSame($confirmed->getKey(), $again->getKey());
        $this->assertTrue($again->isPosted());
        $this->assertSame(2000, $wallet->refresh()->amount->integer());
        $this->assertSame(2, Posting::query()->count());

        Event::assertDispatchedTimes(TransactionConfirmed::class, 1);
        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    /**
     * Both callers read the row as pending, so the guard on status alone cannot
     * separate them. Only one may claim it and go on to write the postings.
     */
    public function testConcurrentConfirmsOnlyPostOnce()
    {
        $wallet = $this->createWallet(0);
        $pending = $wallet->unconfirmedCredit(2000)->refresh();
        // A second caller's read of the row, taken before the winner runs.
        $stale = clone $pending;

        $winner = (new PostTransaction())->confirm($pending);
        $loser = (new PostTransaction())->confirm($stale);

        $this->assertSame($winner->getKey(), $loser->getKey());
        $this->assertTrue($loser->isPosted());
        $this->assertSame(2000, $wallet->refresh()->amount->integer());
        $this->assertSame(2, Posting::query()->count());
    }

    public function testVoidingTwiceIsANoOp()
    {
        $wallet = $this->createWallet(0);
        $pending = $wallet->unconfirmedCredit(2000)->refresh();

        $voided = (new PostTransaction())->void($pending);
        $again = (new PostTransaction())->void(clone $voided);

        $this->assertTrue($again->isVoided());
        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertSame(0, Posting::query()->count());
    }

    /**
     * A void racing a confirm must not leave a voided transaction holding
     * postings; the loser is told, rather than quietly winning.
     */
    public function testVoidingAConfirmedTransactionIsRefused()
    {
        $wallet = $this->createWallet(0);
        $pending = $wallet->unconfirmedCredit(2000)->refresh();
        $stale = clone $pending;

        (new PostTransaction())->confirm($pending);

        $this->expectException(\RuntimeException::class);

        (new PostTransaction())->void($stale);
    }

    public function testConfirmingAVoidedTransactionIsRefused()
    {
        $wallet = $this->createWallet(0);
        $pending = $wallet->unconfirmedCredit(2000)->refresh();
        $stale = clone $pending;

        (new PostTransaction())->void($pending);

        $this->expectException(\RuntimeException::class);

        (new PostTransaction())->confirm($stale);
    }

    /**
     * The retry that matters most is the one after the money already moved. A
     * debit for the whole balance leaves nothing behind, so any balance check
     * standing in front of the replay lookup will reject the retry instead of
     * returning the transaction the first call posted.
     */
    public function testRetryingADebitThatDrainedTheWalletStillReplays()
    {
        $wallet = $this->createWallet(5000);

        $first = $wallet->debit(5000, 'Withdrawal', 'webhook', 'evt_drained');
        $second = $wallet->debit(5000, 'Withdrawal', 'webhook', 'evt_drained');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
    }

    public function testRetryingATransferThatDrainedTheSenderStillReplays()
    {
        $sender = $this->createWallet(4000);
        $receiver = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Bisi',
            'email' => 'bisi@example.com',
        ]));

        $first = $sender->transfer($receiver, 4000, 'Rent', 'evt_drained_transfer');
        $second = $sender->transfer($receiver, 4000, 'Rent', 'evt_drained_transfer');

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(0, $sender->refresh()->amount->integer());
        $this->assertSame(4000, $receiver->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
    }

    /**
     * A retry must not be able to spend money the wallet no longer has. Only a
     * genuine replay of the same key is exempt from the balance check.
     */
    public function testANewKeyStillCannotOverdrawTheWallet()
    {
        $wallet = $this->createWallet(5000);

        $wallet->debit(5000, 'Withdrawal', 'webhook', 'evt_spend');

        $this->expectException(InsufficientBalanceException::class);

        $wallet->debit(5000, 'Withdrawal', 'webhook', 'evt_spend_again');
    }

    /**
     * When two callers race, the pre-flight lookup misses for the loser and the
     * unique index rejects its insert. It must recover by returning the winner's
     * transaction rather than surfacing a database error.
     */
    public function testLosingTheInsertRaceReturnsTheWinnersTransaction()
    {
        $wallet = $this->createWallet(0);
        $house = HouseAccount::walletFor('NGN');

        $draft = fn() => TransactionDraft::singleLeg(
            $wallet,
            $house,
            Posting::DIRECTION_CREDIT,
            Money::NGN(5000),
            'credit_debit',
            'Topup'
        )->idempotent('evt_race');

        $winner = (new PostTransaction())->execute($draft());
        $loser = (new RacingPostTransaction())->execute($draft());

        $this->assertSame($winner->getKey(), $loser->getKey());
        $this->assertSame(5000, $wallet->refresh()->amount->integer());
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame(2, Posting::query()->count());
    }
}

/**
 * Misses the pre-flight idempotency lookup exactly once, reproducing the window
 * in which a competing caller has already committed the same key.
 */
class RacingPostTransaction extends PostTransaction
{
    protected bool $missed = false;

    protected function findByIdempotencyKey(string $key): ?BaseTransaction
    {
        if (!$this->missed) {
            $this->missed = true;

            return null;
        }

        return parent::findByIdempotencyKey($key);
    }
}
