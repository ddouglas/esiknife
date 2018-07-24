<?php

namespace ESIK\Console\Commands\Token;

use ESIK\Models\Member;
use Illuminate\Console\Command;
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
    protected $description = 'Refresh Tokens';

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
        Member::whereNotNull('refresh_token')->where('disabled', 0)->get()->each(function ($member) {
            $this->ssoCont->refresh($member);
        });
    }
}
