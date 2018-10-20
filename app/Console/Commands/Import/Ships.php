<?php

namespace ESIK\Console\Commands\Import;

use ESIK\Models\SDE\{Group};
use ESIK\Models\ESI\{Type};
use ESIK\Jobs\ESI\{GetType};
use Illuminate\Console\Command;

use ESIK\Http\Controllers\DataController;

class Ships extends Command
{
    public $dataCont;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all ships, and their dogma attributes/effects into the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dataCont = new DataController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        

    }
}
