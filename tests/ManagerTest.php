<?php

namespace Walletable\Tests;

use Exception;
use InvalidArgumentException;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Internals\Lockers\LockerInterface;
use Walletable\Internals\Lockers\OptimisticLocker;
use Walletable\Transaction\TransferAction;
use Walletable\WalletableManager;

class ManagerTest extends TestBench
{
    public function testRegisterAction()
    {
        $manager = new WalletableManager();

        $manager->action('transfer', TransferAction::class);

        $this->assertInstanceOf(TransferAction::class, $manager->action('transfer'));
    }

    public function testRegisterActionWithClosure()
    {
        $manager = new WalletableManager();

        $manager->action('transfer', function () {
            return new TransferAction();
        });

        $this->assertInstanceOf(TransferAction::class, $manager->action('transfer'));
    }

    public function testActionClosureResolverReturn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Closure resolver must return an instance of %s',
            ActionInterface::class
        ));

        $manager = new WalletableManager();
        $manager->action('transfer', function () {
            return $this;
        });

        $manager->action('transfer');
    }

    public function testActionNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"transfer" not found as a wallet action');
        $manager = new WalletableManager();
        $manager->action('transfer');
    }

    public function testAddWrongAction()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Action class must implement [%s] interface',
            ActionInterface::class
        ));
        $manager = new WalletableManager();
        $manager->action('transfer', PaymentManagerTest::class);
    }

    public function testWrongActionResolver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'An action can only be resolved through class name or closure'
        );
        $manager = new WalletableManager();
        $manager->action('transfer', []);
    }

    public function testRegisterLocker()
    {
        $manager = new WalletableManager();

        $manager->locker('optimistic', OptimisticLocker::class);

        $this->assertInstanceOf(OptimisticLocker::class, $manager->locker('optimistic'));
    }

    public function testRegisterLockerWithClosure()
    {
        $manager = new WalletableManager();

        $manager->locker('optimistic', function () {
            return new OptimisticLocker();
        });

        $this->assertInstanceOf(OptimisticLocker::class, $manager->locker('optimistic'));
    }

    public function testLockerClosureResolverReturn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Closure resolver must return an instance of %s',
            LockerInterface::class
        ));

        $manager = new WalletableManager();
        $manager->locker('optimistic', function () {
            return $this;
        });

        $manager->locker('optimistic');
    }

    public function testLockerNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"optimistic" not found as a wallet locker');
        $manager = new WalletableManager();
        $manager->locker('optimistic');
    }

    public function testAddWrongLocker()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Locker class must implement [%s] interface',
            LockerInterface::class
        ));
        $manager = new WalletableManager();
        $manager->locker('optimistic', PaymentManagerTest::class);
    }

    public function testWrongLockerResolver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A locker can only be resolved through class name or closure'
        );
        $manager = new WalletableManager();
        $manager->locker('optimistic', []);
    }
}
