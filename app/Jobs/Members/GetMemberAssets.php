<?php

namespace ESIK\Jobs\Members;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Bus, Log;
use ESIK\Models\Member;
use ESIK\Traits\Trackable;
use ESIK\Http\Controllers\DataController;

class GetMemberAssets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public $id, $page, $dataCont;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $id, int $page = null)
    {
        $this->id = $id;
        $this->page = $page;
        $this->dataCont = new DataController;
        $this->prepareStatus();
        if (!is_null($page)) {
            $this->setInput(['id' => $id, 'page' => $page]);
        } else {
            $this->setInput(['id' => $id]);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $member = Member::find($this->id);
        if (is_null($this->page)) {
            $headMemberAssets = $this->dataCont->headMemberAssets($member);
            $status = $headMemberAssets->status;
            $payload = $headMemberAssets->payload;
            if (!$status) {
                Log::alert($payload->message);
                return $status;
            }

            $responseHeaders = collect($payload->headers->response)->recursive();
            if ($responseHeaders->has('X-Pages')) {
                $pages = (int)$responseHeaders->get('X-Pages');
            } else {
                Log::alert($payload->message);
                return $status;
            }

            if (isset($pages)) {
                $dispatchedJobs = collect(); $now = now();
                for($x=1;$x<=$pages;$x++) {
                    $job = new \ESIK\Jobs\Members\GetMemberAssets($member->id, $x);
                    $job->delay($now);
                    Bus::dispatch($job);
                    $dispatchedJobs->push($job->getJobStatusId());
                    $now = $now->addSeconds(1);
                }
                $member->jobs()->attach($dispatchedJobs->toArray());
            }

            return $status;
        } else {
            $getMemberAssets = $this->dataCont->getMemberAssetsByPage($member, $this->page);
            $status = $getMemberAssets->status;
            $payload = $getMemberAssets->payload;
            if (!$status) {
                Log::alert($payload->message);
            }
            return $status;
        }
    }
}
