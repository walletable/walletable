<?php

namespace Walletable\Commands;

use Illuminate\Console\Command;

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
        $this->line("<info>Setting up Walletable</info>");
        $this->line("");
        sleep(2);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.config'
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.migrations'
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.models'
        ]);

        if ($this->confirm('Use uuid for Walletable models?')) {
            $this->configureUuid();
        }

        $this->line("");
        sleep(2);
        $this->line("<info>Walletable installed sucessfully!!</info>");
    }

    /**
     * Configure Walletable migration to use uuid primary keys.
     *
     * @return void
     */
    public function configureUuid()
    {
        // Replace in file for config
        $this->replaceInFile(config_path('walletable.php'), '\'model_uuids\' => false', '\'model_uuids\' => true');

        // Replace in file for Wallet migration
        $this->replaceInFile(
            database_path('migrations/2020_12_25_001500_create_wallets_table.php'),
            '$table->id();',
            '$table->uuid(\'id\')->primary();'
        );

        // Replace in file for Transaction migration
        $this->replaceInFile(
            database_path('migrations/2020_12_25_001600_create_transactions_table.php'),
            '$table->id();',
            '$table->uuid(\'id\')->primary();'
        );
        $this->replaceInFile(
            database_path('migrations/2020_12_25_001600_create_transactions_table.php'),
            '$table->unsignedBigInteger(\'wallet_id\')->index();',
            '$table->uuid(\'wallet_id\')->index();'
        );
    }

    /**
     * Replace a given string in a given file.
     *
     * @param  string  $path
     * @param  string  $search
     * @param  string  $replace
     * @return void
     */
    protected function replaceInFile($path, $search, $replace)
    {
        file_put_contents(
            $path,
            str_replace($search, $replace, file_get_contents($path))
        );
    }
}
