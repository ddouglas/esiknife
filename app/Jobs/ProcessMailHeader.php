<?php

namespace ESIK\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use ESIK\Models\{Member};
use ESIK\Traits\Trackable;
use ESIK\Models\ESI\{MailHeader, MailingList};
use Illuminate\Support\Collection;
use ESIK\Http\Controllers\DataController;

class ProcessMailHeader implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public $memberId, $header, $recipients, $dataCont;

    /**
     * Create a new job instance.
     *
     * @param ESIK\Models\Member $member Instance of Members for the character that we are retrieving the mail for.
     * @param int $id ID of the Mail that are receiving the body for.
     * @return void
     */
    public function __construct(int $memberId, string $header, string $recipients)
    {
        $this->dataCont = new DataController();
        $this->memberId = $memberId;
        $this->header = $header;
        $this->recipients = $recipients;
        $this->prepareStatus();
        $this->setInput(['memberId' => $memberId, 'header' => $header, 'recipients' => $recipients]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $process = $this->dataCont->processMailHeader($this->memberId, $this->header);
        $status = $process->status;
        $payload = $process->payload;
        if (!$status) {
            return $status;
        }
    }
}
