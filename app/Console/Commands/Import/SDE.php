<?php

namespace ESIK\Console\Commands\Import;

use Illuminate\Console\Command;
use ESIK\Http\Controllers\SDEController;

class SDE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:sde';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports specific tables from the SDE that have been outlined in the config.';

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
        foreach (config('services.eve.sde.import') as $type) {
            SDEController::{$type}();
            sleep(2);
        }
        return true;
    }
}
