<?php

namespace Walletable\Commands;

use Walletable\Enums\ModelID;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'walletable:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepares Walletable for use';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
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

        $this->configureUuid(ModelID::from($this->choice('Choose your model ID for Walletable primary key', ['default', 'uuid', 'ulid'], 'default')));

        $this->line('<info>Walletable installed sucessfully!!!</info>');

        return;
    }
    
    /**
     * Check if Walletable was already installed
     *
     * @return bool
     */
    private function checkIfAlreadyInstalled(): bool
    {
        return File::exists(config_path('walletable.php')) ||
                File::exists(app_path('Models/Wallet.php')) ||
                File::exists(app_path('Models/Transaction.php')) ||
                File::exists(database_path('migrations/2020_12_25_001500_create_wallets_table.php')) ||
                File::exists(database_path('migrations/2020_12_25_001600_create_transactions_table.php'));
    }

    /**
     * Configure Walletable migration to use uuid primary keys.
     *
     * @param string $modelID
     * 
     * @return void
     */
    private function configureUuid(string $modelID)
    {
        if ($modelID !== 'default') {

            // Replace in file for config
            $this->replaceInFile(config_path('walletable.php'), '\'model_id\' => \'default\'', '\'model_id\' => \'' . $modelID . '\'');

            if ($modelID === 'uuid') {
                $table = ['$table->uuid(\'id\')->primary();', '$table->uuid(\'wallet_id\')->index();'];
            } else if ($modelID === 'ulid') {
                $table = ['$table->ulid(\'id\')->primary();', '$table->ulid(\'wallet_id\')->index();'];
            } else {
                $table = ['$table->id();', '$table->unsignedBigInteger(\'wallet_id\')->index();'];
            }

            // Replace in file for Wallet migration
            $this->replaceInFile(
                database_path('migrations/2020_12_25_001500_create_wallets_table.php'),
                '$table->id();',
                $table[0]
            );

            // Replace in file for Transaction migration
            $this->replaceInFile(
                database_path('migrations/2020_12_25_001600_create_transactions_table.php'),
                '$table->id();',
                $table[0]
            );
            $this->replaceInFile(
                database_path('migrations/2020_12_25_001600_create_transactions_table.php'),
                '$table->unsignedBigInteger(\'wallet_id\')->index();',
                $table[1]
            );
        }
    }

    /**
     * Replace a given string in a given file.
     *
     * @param  string  $path
     * @param  string  $search
     * @param  string  $replace
     * @return void
     */
    protected function replaceInFile(string $path, string $search, string $replace)
    {
        file_put_contents(
            $path,
            str_replace($search, $replace, file_get_contents($path))
        );
    }
}
