<?php

namespace ESIK\Jobs\ESI;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use ESIK\Models\Member;
use ESIK\Http\Controllers\DataController;

use Illuminate\Support\Collection;

class GetContractItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $member, $contract, $dataCont;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Member $member, Collection $contract)
    {
        $this->member = $member;
        $this->contract = $contract;
        $this->dataCont = new DataController;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return $this->dataCont->getMemberContractItems($this->member, $this->contract->get('contract_id'))->status;
    }
}
