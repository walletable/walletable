<?php

namespace Walletable\Tests;

use Walletable\Exceptions\IncompatibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Exceptions\WalletableException;
use Walletable\Money\Money;
use Walletable\Tests\Models\Wallet;

class ExceptionTest extends TestBench
{
    public function testIncompatibleWalletsException()
    {

        try {
            throw new IncompatibleWalletsException(
                $wallet = new Wallet(),
                $against = new Wallet(),
            );
        } catch (IncompatibleWalletsException $exception) {
            $this->assertSame($exception->getWallet(), $wallet);
            $this->assertSame($exception->getAgainst(), $against);
        }
    }

    public function testInsufficientBalanceException()
    {
        $this->setUpCurrencies();

        try {
            throw new InsufficientBalanceException(
                $wallet = new Wallet(),
                $money = Money::NGN(100000),
            );
        } catch (InsufficientBalanceException $exception) {
            $this->assertSame($exception->getWallet(), $wallet);
            $this->assertSame($exception->getAmount(), $money);
        }
    }

    /**
     * Domain exceptions used to extend AssertionError, which sits under Error
     * rather than Exception, so the most common catch block missed them.
     */
    public function testDomainExceptionsAreCaughtByAGenericExceptionHandler()
    {
        $this->setUpCurrencies();

        $exceptions = [
            new IncompatibleWalletsException(new Wallet(), new Wallet()),
            new InsufficientBalanceException(new Wallet(), Money::NGN(100)),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(WalletableException::class, $exception);
            $this->assertInstanceOf(\RuntimeException::class, $exception);

            $caught = null;
            try {
                throw $exception;
            } catch (\Exception $e) {
                $caught = $e;
            }

            $this->assertSame($exception, $caught);
        }
    }

    /**
     * The misspelled class name is kept as an alias so existing catch blocks
     * keep working against the correctly spelled exception the package throws.
     */
    public function testDeprecatedIncompactibleAliasResolvesToTheSameClass()
    {
        $exception = new IncompatibleWalletsException(new Wallet(), new Wallet());

        $this->assertInstanceOf(\Walletable\Exceptions\IncompactibleWalletsException::class, $exception);
    }
}
