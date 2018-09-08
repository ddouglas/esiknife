<?php

namespace ESIK\Console\Commands\Clean;

use Log;
use ESIK\Models\Member;
use Illuminate\Console\Command;

class CleanDisabled extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:disabled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Disabled Recorded from the members table';

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
        $deleted = Member::where('disabled', 1)->delete();
        $message = "Successfully Removed {$deleted} records from the database";
        $this->info($message);
        Log::info($message);
    }
}
