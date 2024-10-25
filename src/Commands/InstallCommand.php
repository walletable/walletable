<?php

namespace Walletable\Commands;

use Illuminate\Console\Command;
use Walletable\Enums\ModelID;

use function Laravel\Prompts\select;

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

        $this->call('vendor:publish', [
            '--tag' => 'walletable.config'
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.migrations'
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'walletable.models'
        ]);

        $this->configureUuid(ModelID::from(select(
            label: 'Choose your model ID?',
            options: ['default', 'uuid', 'ulid'],
            default: 'default',
            hint: 'What will u like to use for Walletable primary key.',
            required: true
        )));

        $this->line("");

        $this->line("<info>Walletable installed sucessfully!!</info>");
    }

    /**
     * Configure Walletable migration to use uuid primary keys.
     *
     * @param \Walletable\Enums\ModelID $modelID
     * 
     * @return void
     */
    private function configureUuid(ModelID $modelID)
    {
        if ($modelID !== ModelID::DEFAULT) {

            // Replace in file for config
            $this->replaceInFile(config_path('walletable.php'), '\'model_id\' => \'default\'', '\'model_id\' => \'' . $modelID->value . '\'');

            $table = match ($modelID) {
                ModelID::UUID => ['$table->uuid(\'id\')->primary();', '$table->uuid(\'wallet_id\')->index();'],
                ModelID::ULID => ['$table->ulid(\'id\')->primary();', '$table->ulid(\'wallet_id\')->index();'],
                default => ['$table->id();', '$table->unsignedBigInteger(\'wallet_id\')->index();']
            };

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
