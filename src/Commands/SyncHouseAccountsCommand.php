<?php

namespace Walletable\Commands;

use Illuminate\Console\Command;
use Walletable\Models\HouseAccount;
use Walletable\Money\Money;

/**
 * Ensure exactly one HouseAccount row (and its mirrored wallet) exists for
 * every currency registered with the Money facade. Idempotent.
 */
class SyncHouseAccountsCommand extends Command
{
    protected $signature = 'walletable:houses:sync';

    protected $description = 'Ensure a house account + wallet exists for every registered currency.';

    public function handle(): int
    {
        $currencies = $this->resolveCurrencies();

        if (empty($currencies)) {
            $this->warn('No currencies registered with Money. Register currencies before running this command.');
            return self::SUCCESS;
        }

        foreach ($currencies as $code) {
            $wallet = HouseAccount::walletFor($code);
            $this->line(sprintf(
                '<info>%s</info> house wallet ready (id=%s, balance=%d).',
                $code,
                $wallet->getKey(),
                $wallet->amount->integer()
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    protected function resolveCurrencies(): array
    {
        // Money::getCurrencies isn't exposed publicly; reach into the static
        // registry via reflection to avoid coupling to a getter we don't own.
        $ref = new \ReflectionClass(Money::class);
        if (!$ref->hasProperty('currencies')) {
            return [];
        }
        $prop = $ref->getProperty('currencies');
        $prop->setAccessible(true);
        $value = $prop->getValue();
        if (!is_array($value)) {
            return [];
        }
        return array_keys($value);
    }
}
