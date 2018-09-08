<?php

namespace ESIK\Jobs\ESI;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Log;
use ESIK\Models\Member;
use ESIK\Traits\Trackable;
use ESIK\Http\Controllers\DataController;

class GetContractItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public $memberId, $contractId, $dataCont;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $memberId, int $contractId)
    {
        $this->memberId = $memberId;
        $this->contractId = $contractId;
        $this->dataCont = new DataController;
        $this->prepareStatus();
        $this->setInput(['memberId' => $memberId, 'contractId' => $contractId]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $member = Member::findOrFail($this->memberId);
        $getMemberContractItems = $this->dataCont->getMemberContractItems($member, $this->contractId);
        $status = $getMemberContractItems->status;
        $payload = $getMemberContractItems->payload;
        if (!$status) {
            Log::alert($payload->message);
        }
        return $status;
    }
}
