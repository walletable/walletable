<?php

namespace Walletable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'walletable:install';

    protected $description = 'Prepares Walletable for use';

    public function handle()
    {
        $this->line('<info>Setting up Walletable</info>');

        $overwrite = $this->checkIfAlreadyInstalled();

        if ($overwrite && !$this->confirm('It seems Walletable was installed before. Do you want to overwrite existing settings?')) {
            $this->line('<info>Installation aborted.</info>');
            return;
        }

        $this->call('vendor:publish', [
            '--tag' => 'walletable.config',
            '--force' => $overwrite,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.migrations',
            '--force' => $overwrite,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.models',
            '--force' => $overwrite,
        ]);

        $modelId = $this->choice(
            'Choose your model ID for Walletable primary key',
            ['default', 'uuid', 'ulid'],
            'default'
        );
        $this->configureModelId($modelId);

        $this->line('<info>Walletable installed sucessfully!!!</info>');

        return;
    }

    private function checkIfAlreadyInstalled(): bool
    {
        return File::exists(config_path('walletable.php'))
            || File::exists(app_path('Models/Wallet.php'))
            || File::exists(app_path('Models/Transaction.php'))
            || File::exists(app_path('Models/Posting.php'))
            || File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php'))
            || File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php'))
            || File::exists(database_path('migrations/2020_12_25_001700_create_postings_table.php'));
    }

    /**
     * Rewrite published migrations to use the chosen PK strategy. UUID/ULID is
     * applied uniformly to wallets, house_accounts, transactions, and postings
     * (and to the FK columns that reference them).
     */
    private function configureModelId(string $modelId): void
    {
        if ($modelId === 'default') {
            return;
        }

        $this->replaceInFile(
            config_path('walletable.php'),
            '\'model_id\' => \'default\'',
            '\'model_id\' => \'' . $modelId . '\''
        );

        [$id, $walletFk, $txFk] = $modelId === 'uuid'
            ? [
                '$table->uuid(\'id\')->primary();',
                '$table->uuid(\'wallet_id\')->index();',
                '$table->uuid(\'transaction_id\')->index();',
            ]
            : [
                '$table->ulid(\'id\')->primary();',
                '$table->ulid(\'wallet_id\')->index();',
                '$table->ulid(\'transaction_id\')->index();',
            ];

        $migrations = [
            'migrations/2020_12_25_001500_create_wallets_table.php',
            'migrations/2020_12_25_001550_create_house_accounts_table.php',
            'migrations/2020_12_25_001600_create_transactions_table.php',
            'migrations/2020_12_25_001700_create_postings_table.php',
        ];
        foreach ($migrations as $m) {
            $path = database_path($m);
            if (!File::exists($path)) {
                continue;
            }
            $this->replaceInFile($path, '$table->id();', $id);
        }

        $postings = database_path('migrations/2020_12_25_001700_create_postings_table.php');
        if (File::exists($postings)) {
            $this->replaceInFile(
                $postings,
                '$table->unsignedBigInteger(\'wallet_id\')->index();',
                $walletFk
            );
            $this->replaceInFile(
                $postings,
                '$table->unsignedBigInteger(\'transaction_id\')->index();',
                $txFk
            );
        }

        $transactions = database_path('migrations/2020_12_25_001600_create_transactions_table.php');
        if (File::exists($transactions)) {
            $this->replaceInFile(
                $transactions,
                '$table->unsignedBigInteger(\'reverses_id\')->nullable();',
                $modelId === 'uuid'
                    ? '$table->uuid(\'reverses_id\')->nullable();'
                    : '$table->ulid(\'reverses_id\')->nullable();'
            );
        }
    }

    protected function replaceInFile(string $path, string $search, string $replace): void
    {
        file_put_contents(
            $path,
            str_replace($search, $replace, file_get_contents($path))
        );
    }
}
