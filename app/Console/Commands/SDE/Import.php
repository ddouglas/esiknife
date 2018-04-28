<?php

namespace ESIK\Console\Commands\SDE;

use Illuminate\Console\Command;
use ESIK\Http\Controllers\SdeController;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sde:import';

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
        $count = count(config('services.eve.sde.import'));
        $x = 1;
        foreach (config('services.eve.sde.import') as $type) {
            SdeController::{$type}();
            dump("{$x} of {$count} imported successfully");
            $x++;
            sleep(5);
        }
        return true;
    }
}
