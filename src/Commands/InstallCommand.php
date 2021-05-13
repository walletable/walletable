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
    protected $description = 'Installs Walletable';

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

        $this->line("");
        sleep(2);

        $this->line("<info> Walletable installed sucessfully!!</info>");
        

    }

}