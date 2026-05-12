<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\Event;
use Walletable\Events\TransactionPending;
use Walletable\Events\TransactionPosted;
use Walletable\Internals\Actions\Action;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Internals\Argument;
use Walletable\Tests\Models\Walletable;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\TransferAction;

class ActionTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testCredit()
    {
        Event::fake([TransactionPosted::class]);
        $wallet = $this->createWallet();

        $action = new Action($wallet, $actionObj = new CreditDebitAction());

        $entry = $action->credit(100000, new ActionData($wallet), 'Test Credit');

        $this->assertSame(100000, $wallet->refresh()->amount->integer());

        $userPosting = $entry->postings()->where('wallet_id', $wallet->id)->first();
        $this->assertSame(100000, $userPosting->amount->integer());
        $this->assertSame('Credit', $userPosting->title);
        $this->assertSame('credit', $userPosting->type);
        $this->assertSame('Test Credit', $entry->narration);

        $this->assertTrue($actionObj->supportCredit());
        $this->assertTrue($actionObj->supportDebit());

        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testUnconfirmedCredit()
    {
        Event::fake([TransactionPosted::class, TransactionPending::class]);
        $wallet = $this->createWallet();

        $action = new Action($wallet, new CreditDebitAction());

        $pending = $action->unconfirmedCredit(100000, new ActionData($wallet), 'Test Unconfirmed Credit');

        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertSame('pending', $pending->status);
        $this->assertCount(0, $pending->postings()->get());
        $this->assertSame('Test Unconfirmed Credit', $pending->narration);

        Event::assertDispatchedTimes(TransactionPending::class, 1);
        Event::assertDispatchedTimes(TransactionPosted::class, 0);

        $confirmed = $wallet->confirm($pending->refresh());

        $this->assertSame(100000, $wallet->refresh()->amount->integer());
        $this->assertSame('posted', $confirmed->status);
        $this->assertCount(2, $confirmed->postings()->get());

        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testUnconfirmedDebit()
    {
        Event::fake([TransactionPosted::class, TransactionPending::class]);
        $wallet = $this->createWallet(1000000);
        $house = \Walletable\Models\HouseAccount::walletFor('NGN');
        $house->forceFill(['amount' => -1000000])->save();

        $action = new Action($wallet, new CreditDebitAction());

        $pending = $action->unconfirmedDebit(500000, new ActionData($wallet), 'Test Unconfirmed Debit');

        $this->assertSame(1000000, $wallet->refresh()->amount->integer());
        $this->assertSame('pending', $pending->status);

        Event::assertDispatchedTimes(TransactionPending::class, 1);
        Event::assertDispatchedTimes(TransactionPosted::class, 0);

        $wallet->confirm($pending->refresh());

        $this->assertSame(500000, $wallet->refresh()->amount->integer());
        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testDebit()
    {
        $wallet = $this->createWallet(100000);
        $house = \Walletable\Models\HouseAccount::walletFor('NGN');
        $house->forceFill(['amount' => -100000])->save();

        $action = new Action($wallet, new CreditDebitAction());

        $entry = $action->debit(100000, new ActionData($wallet), 'Test Debit');

        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $userPosting = $entry->postings()->where('wallet_id', $wallet->id)->first();
        $this->assertSame(100000, $userPosting->amount->integer());
        $this->assertSame('Debit', $userPosting->title);
        $this->assertSame('debit', $userPosting->type);
    }

    public function testActionData()
    {
        $data = new ActionData(
            'Wellcome',
            200
        );

        $this->assertTrue($data->keyExists(0));
        $this->assertTrue($data->keyExists(1));
        $this->assertNotTrue($data->keyExists(2));

        $this->assertSame('Wellcome', $data->getKeyValue(0));
        $this->assertSame(200, $data->getKeyValue(1));
        $this->assertSame(null, $data->getKeyValue(2));

        $this->assertInstanceOf(Argument::class, $data->argument(0));
    }

    public function testActionManager()
    {
        $wallet = $this->createWallet(100000);
        $entry = $wallet->credit(50000, 'Test Credit');

        $posting = $entry->postings()->where('wallet_id', $wallet->id)->first();
        $manager = new ActionManager($posting, new TestAction());

        $this->assertSame('Test Transaction', $manager->title());
        $this->assertSame('/image/test/transaction.jpg', $manager->image());
    }

    public function testTransactionGetMethodResource()
    {
        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $entry = $wallet->transfer($wallet2, 50000, 'Test transfer');
        $creditPosting = $entry->postings()->where('direction', 'C')->first();

        $this->assertSame($wallet->walletable->id, $creditPosting->getMethodResource()->id);
    }

    public function testTransfer()
    {
        TransferAction::methodResourceUsing(function ($action, $posting) {
            return ['resource'];
        });

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $entry = $wallet->transfer($wallet2, 50000, 'Test transfer');
        $creditPosting = $entry->postings()->where('direction', 'C')->first();

        $this->assertSame(['resource'], $creditPosting->getMethodResource());

        // Reset to avoid bleeding into other tests.
        TransferAction::methodResourceUsing(function ($action, $posting) {
            return $posting->method;
        });
    }
}
