<?php

namespace ESIK\Console\Commands\Token;

use Illuminate\Console\Command;

use ESIK\Models\Member;
use ESIK\Http\Controllers\SSOController;

class Refresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loops through all members and dispatches a Refresh Token Job.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->ssoCont = new SSOController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $members = Member::where('disabled', 0)->whereNotNull('refresh_token')->get()->each(function ($member) {
            $this->ssoCont->refresh($member);
        });
    }
}
