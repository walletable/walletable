<?php

namespace Walletable\Tests;

use Walletable\Exceptions\IncompactibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Money\Money;
use Walletable\Tests\Models\Wallet;

class ExceptionTest extends TestBench
{
    public function testIncompactibleWalletsException()
    {

        try {
            throw new IncompactibleWalletsException(
                $wallet = new Wallet(),
                $against = new Wallet(),
            );
        } catch (IncompactibleWalletsException $exception) {
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
}
