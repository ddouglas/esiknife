<?php

namespace ESIK\Console\Commands\Token;

use Carbon, Log;
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
    protected $signature = 'token:refresh {id? : Id of token to refresh}';

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
        $id = $this->argument('id');
        if (!is_null($id)) {
            $member = Member::findOrFail($id);
            if (is_null($member->refresh_token)) {
                $this->error("Member ". $id . " does not have a refresh token saved. Canceling refresh request");
                return false;
            }
            $refresh = $this->ssoCont->refresh($member);
            $status = $refresh->status;
            $payload = $refresh->payload;
            if (!$status) {
                $this->error($payload->message);
                Log::alert($payload->message);
                return false;
            } else {
                $this->info('Token for '. $member->id. " Refreshed Successfully.");
            }
        } else {
            $success = 0; $fail = 0;
            Member::whereNotNull('refresh_token')->where('disabled', 0)->chunk(250, function ($chunk) use (&$success, &$fail) {
                $chunk->each(function ($member) use (&$success, &$fail) {
                    $refresh = $this->ssoCont->refresh($member);
                    $status = $refresh->status;
                    $payload = $refresh->payload;
                    if (!$status) {
                        $fail++;
                        $this->error($payload->message. " || Attempts Failed: {$fail}");
                        Log::alert($payload->message);
                    } else {
                        $success++;
                        $this->info('Token for '. $member->id. " Refreshed Successfully. || Attempts Successful: {$success}");
                    }
                    usleep(5000);
                });
                usleep(500000);
            });
        }

        return true;
    }
}
