<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\File;

class InstallationTest extends TestBench
{
    public function testInstallation()
    {
        $this->confirmInstallation('default');

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../config/walletable.php'),
            file_get_contents(config_path('walletable.php'))
        );
        foreach (['Wallet.php', 'Transaction.php', 'Posting.php'] as $model) {
            $this->assertEquals(
                file_get_contents(__DIR__ . '/../database/models/' . $model),
                file_get_contents(app_path('Models/' . $model))
            );
        }
        foreach ($this->migrationFiles() as $m) {
            $this->assertEquals(
                file_get_contents(__DIR__ . '/../database/migrations/' . $m),
                file_get_contents(database_path('migrations/' . $m))
            );
        }

        $this->cleanUpInstallation();
    }

    public function testInstallationUseUuid()
    {
        $this->confirmInstallation('uuid');

        $this->assertStringContainsString(
            '\'model_id\' => \'uuid\'',
            file_get_contents(config_path('walletable.php'))
        );

        $this->assertStringContainsString(
            '$table->uuid(\'id\')->primary();',
            file_get_contents(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
        );

        $this->assertStringContainsString(
            '$table->uuid(\'wallet_id\')->index();',
            file_get_contents(database_path('migrations/2020_12_25_001700_create_postings_table.php'))
        );

        $this->assertStringContainsString(
            '$table->uuid(\'transaction_id\')->index();',
            file_get_contents(database_path('migrations/2020_12_25_001700_create_postings_table.php'))
        );

        $this->cleanUpInstallation();
    }

    public function testInstallationUseUlid()
    {
        $this->confirmInstallation('ulid');

        $this->assertStringContainsString(
            '\'model_id\' => \'ulid\'',
            file_get_contents(config_path('walletable.php'))
        );

        $this->assertStringContainsString(
            '$table->ulid(\'id\')->primary();',
            file_get_contents(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
        );

        $this->assertStringContainsString(
            '$table->ulid(\'wallet_id\')->index();',
            file_get_contents(database_path('migrations/2020_12_25_001700_create_postings_table.php'))
        );

        $this->cleanUpInstallation();
    }

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
        foreach (['Wallet.php', 'Transaction.php', 'Posting.php'] as $model) {
            $this->assertTrue(File::exists(app_path('Models/' . $model)));
        }
        foreach ($this->migrationFiles() as $m) {
            $this->assertTrue(File::exists(database_path('migrations/' . $m)));
        }

        $this->cleanUpInstallation();
    }

    /**
     * @return string[]
     */
    private function migrationFiles(): array
    {
        return [
            '2020_12_25_001500_create_wallets_table.php',
            '2020_12_25_001550_create_house_accounts_table.php',
            '2020_12_25_001600_create_transactions_table.php',
            '2020_12_25_001700_create_postings_table.php',
        ];
    }

    private function cleanUpInstallation(): void
    {
        if (File::exists(config_path('walletable.php'))) {
            unlink(config_path('walletable.php'));
        }

        foreach (['Wallet.php', 'Transaction.php', 'Posting.php', 'JournalEntry.php'] as $model) {
            $path = app_path('Models/' . $model);
            if (File::exists($path)) {
                unlink($path);
            }
        }

        foreach ($this->migrationFiles() as $m) {
            $path = database_path('migrations/' . $m);
            if (File::exists($path)) {
                unlink($path);
            }
        }
    }

    private function confirmInstallation(string $modelId): void
    {
        $this->cleanUpInstallation();

        $this->assertFalse(File::exists(config_path('walletable.php')));
        foreach ($this->migrationFiles() as $m) {
            $this->assertFalse(File::exists(database_path('migrations/' . $m)));
        }

        $command = $this->artisan('walletable:install');
        $command->expectsChoice(
            'Choose your model ID for Walletable primary key',
            $modelId,
            ['default', 'uuid', 'ulid']
        );
        $command->execute();
        $command->expectsOutput('Setting up Walletable');

        $this->assertTrue(File::exists(config_path('walletable.php')));
        foreach (['Wallet.php', 'Transaction.php', 'Posting.php'] as $model) {
            $this->assertTrue(File::exists(app_path('Models/' . $model)));
        }
        foreach ($this->migrationFiles() as $m) {
            $this->assertTrue(File::exists(database_path('migrations/' . $m)));
        }
    }
}
