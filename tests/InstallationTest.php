<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\File;

class InstallationTest extends TestBench
{
    public function testInstallation()
    {
        $this->confirmInstallation('default');

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

        $this->cleanUpInstallation();
    }

    public function testInstallationUseUuid()
    {
        $this->confirmInstallation('uuid');

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
            '\'model_id\' => \'uuid\'',
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

        $this->cleanUpInstallation();
    }

    public function testInstallationUseUlid()
    {
        $this->confirmInstallation('ulid');

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
            '\'model_id\' => \'ulid\'',
            file_get_contents(config_path('walletable.php'))
        );

        $this->assertStringContainsString(
            '$table->ulid(\'id\')->primary();',
            file_get_contents(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
        );

        $this->assertStringContainsString(
            '$table->ulid(\'id\')->primary();',
            file_get_contents(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
        );

        $this->assertStringContainsString(
            '$table->ulid(\'wallet_id\')->index();',
            file_get_contents(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
        );

        $this->cleanUpInstallation();
    }

    private function cleanUpInstallation()
    {
        (!File::exists(config_path('walletable.php'))) || unlink(config_path('walletable.php'));

        (!File::exists(app_path('Models/Wallet.php'))) || unlink(app_path('Models/Wallet.php'));

        (!File::exists(app_path('Models/Transaction.php'))) || unlink(app_path('Models/Transaction.php'));

        (!File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))) || unlink(database_path('migrations/2020_12_25_001500_create_wallets_table.php'));

        (!File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))) || unlink(database_path('migrations/2020_12_25_001600_create_transactions_table.php'));
    }

    private function confirmInstallation(string $model_id)
    {
        $this->cleanUpInstallation();

        $this->assertFalse(File::exists(config_path('walletable.php')));
        $this->assertFalse(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));
        $this->assertFalse(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));
        $this->assertFalse(File::exists(app_path('Models/Wallet.php')));
        $this->assertFalse(File::exists(app_path('Models/Transaction.php')));

        $command = $this->artisan('walletable:install');

        $command->expectsChoice('Choose your model ID for Walletable primary key', $model_id, ['default', 'uuid', 'ulid']);

        $command->execute();

        $command->expectsOutput('Setting up Walletable');

        // $command->expectsOutput('Walletable installed sucessfully!!!');

        $this->assertTrue(File::exists(config_path('walletable.php')));

        $this->assertTrue(File::exists(app_path('Models/Wallet.php')));

        $this->assertTrue(File::exists(app_path('Models/Transaction.php')));

        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));

        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));
    }

    // Test that the command detects an existing installation
    public function testDetectsExistingInstallation()
    {
        $this->confirmInstallation('default');

        $command = $this->artisan('walletable:install');

        $command->expectsConfirmation('It seems Walletable was installed before. Do you want to overwrite existing settings?', 'no');

        $command->execute();

        $command->expectsOutput('Setting up Walletable');

        $command->expectsOutput('Installation aborted.');

        $command->assertExitCode(0);

        $this->cleanUpInstallation();
    }

    // Test overwriting existing files after confirmation
    public function testOverwritesExistingInstallationAfterConfirmation()
    {
        $this->confirmInstallation('default');

        $command = $this->artisan('walletable:install');

        $command->expectsConfirmation('It seems Walletable was installed before. Do you want to overwrite existing settings?', 'yes');

        $command->expectsChoice('Choose your model ID for Walletable primary key', 'default', ['default', 'uuid', 'ulid']);

        $command->execute();

        $command->expectsOutput('Setting up Walletable');

        $command->expectsOutput('Walletable installed sucessfully!!!');

        $command->assertExitCode(0);

        $this->assertTrue(File::exists(config_path('walletable.php')));

        $this->assertTrue(File::exists(app_path('Models/Wallet.php')));

        $this->assertTrue(File::exists(app_path('Models/Transaction.php')));

        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')));

        $this->assertTrue(File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php')));

        $this->cleanUpInstallation();
    }
}
