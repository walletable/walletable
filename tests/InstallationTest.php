<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\File;

class InstallationTest extends TestBench
{
    public function testInstallation()
    {
        // make sure we're starting from a clean state
            (!File::exists(config_path('walletable.php'))) || unlink(config_path('walletable.php'));
            (!File::exists(app_path('Models/Wallet.php'))) || unlink(app_path('Models/Wallet.php'));
            (!File::exists(app_path('Models/Transaction.php'))) || unlink(app_path('Models/Transaction.php'));
            (!File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))) ||
                unlink(database_path('migrations/2020_12_25_001500_create_wallets_table.php'));
            (!File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))) ||
                unlink(database_path('migrations/2020_12_25_001600_create_transactions_table.php'));

        $this->assertFalse(File::exists(config_path('walletable.php')));
        $this->assertFalse(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));
        $this->assertFalse(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));
        // When we run the install command
        $command = $this->artisan('walletable:install');

        // We expect a warning that our configuration file exists
        $command->expectsConfirmation(
            'Use uuid for walletable models?',
            // When answered with "yes"
            'no'
        );

        // execute the command to force override
        $command->execute();
        $command->expectsOutput("<info>Setting up Walletable</info>");
        $command->expectsOutput("<info>Walletable installed sucessfully!!</info>");

        // Assert that the original contents are present
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../config/walletable.php'),
            file_get_contents(config_path('walletable.php'))
        );
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../database/models/Wallet.php'),
            file_get_contents(app_path('Models/Wallet.php'))
        );
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../database/models/Transaction.php'),
            file_get_contents(app_path('Models/Transaction.php'))
        );
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../database/migrations/2020_12_25_001500_create_wallets_table.php'),
            file_get_contents(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
        );
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../database/migrations/2020_12_25_001600_create_transactions_table.php'),
            file_get_contents(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
        );

        $this->assertTrue(File::exists(config_path('walletable.php')));
        $this->assertTrue(File::exists(app_path('Models/Wallet.php')));
        $this->assertTrue(File::exists(app_path('Models/Transaction.php')));
        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));
        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));

        // Clean up
        unlink(config_path('walletable.php'));
        unlink(app_path('Models/Wallet.php'));
        unlink(app_path('Models/Transaction.php'));
        unlink(database_path('migrations/2020_12_25_001500_create_wallets_table.php'));
        unlink(database_path('migrations/2020_12_25_001600_create_transactions_table.php'));
    }

    public function testInstallationUseUuid()
    {
        // make sure we're starting from a clean state
            (!File::exists(config_path('walletable.php'))) || unlink(config_path('walletable.php'));
            (!File::exists(app_path('Models/Wallet.php'))) || unlink(app_path('Models/Wallet.php'));
            (!File::exists(app_path('Models/Transaction.php'))) || unlink(app_path('Models/Transaction.php'));
            (!File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))) ||
                unlink(database_path('migrations/2020_12_25_001500_create_wallets_table.php'));
            (!File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))) ||
                unlink(database_path('migrations/2020_12_25_001600_create_transactions_table.php'));

        $this->assertFalse(File::exists(config_path('walletable.php')));
        $this->assertFalse(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));
        $this->assertFalse(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));
        // When we run the install command
        $command = $this->artisan('walletable:install');

        // We expect a warning that our configuration file exists
        $command->expectsConfirmation(
            'Use uuid for walletable models?',
            // When answered with "yes"
            'yes'
        );

        // execute the command to force override
        $command->execute();
        $command->expectsOutput("<info>Setting up Walletable</info>");
        $command->expectsOutput("<info>Walletable installed sucessfully!!</info>");

        // Assert that the original contents are present
        $this->assertNotEquals(
            file_get_contents(__DIR__ . '/../config/walletable.php'),
            file_get_contents(config_path('walletable.php'))
        );
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../database/models/Wallet.php'),
            file_get_contents(app_path('Models/Wallet.php'))
        );
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../database/models/Transaction.php'),
            file_get_contents(app_path('Models/Transaction.php'))
        );
        $this->assertNotEquals(
            file_get_contents(__DIR__ . '/../database/migrations/2020_12_25_001500_create_wallets_table.php'),
            file_get_contents(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
        );
        $this->assertNotEquals(
            file_get_contents(__DIR__ . '/../database/migrations/2020_12_25_001600_create_transactions_table.php'),
            file_get_contents(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
        );

        $this->assertStringContainsString(
            '\'model_id\' => default',
            file_get_contents(config_path('walletable.php'))
        );
        $this->assertStringContainsString(
            '$table->uuid(\'id\')->primary();',
            file_get_contents(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
        );
        $this->assertStringContainsString(
            '$table->uuid(\'id\')->primary();',
            file_get_contents(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
        );
        $this->assertStringContainsString(
            '$table->uuid(\'wallet_id\')->index();',
            file_get_contents(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
        );

        $this->assertTrue(File::exists(config_path('walletable.php')));
        $this->assertTrue(File::exists(app_path('Models/Wallet.php')));
        $this->assertTrue(File::exists(app_path('Models/Transaction.php')));
        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));
        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));

        // Clean up
        unlink(config_path('walletable.php'));
        unlink(app_path('Models/Wallet.php'));
        unlink(app_path('Models/Transaction.php'));
        unlink(database_path('migrations/2020_12_25_001500_create_wallets_table.php'));
        unlink(database_path('migrations/2020_12_25_001600_create_transactions_table.php'));
    }
}
