<?php

namespace ESIK\Console\Commands\Clean;

use Log;
use ESIK\Models\Member;
use Illuminate\Console\Command;

class Stale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $deleted = Member::whereNull('access_token')->delete();
        $message = "Successfully Removed {$deleted} records from the database";
        $this->info($message);
        Log::info($message);
    }
}
