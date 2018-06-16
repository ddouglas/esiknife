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
        $member = Member::findOrFail($this->memberId);
        $header = collect(json_decode($this->header, true))->recursive();
        $header = MailHeader::findOrFail($header->get('id'));
        $recipients = collect(json_decode($this->recipients, true))->recursive();
        $getMemberMailBody = $this->dataCont->getMemberMailBody($member, $header->id);
        $status = $getMemberMailBody->status;
        $payload = $getMemberMailBody->payload;
        if (!$status) {
            return $status;
        }
        unset($status, $payload);
        $recipients->each(function ($recipient) use ($header) {
            if ($recipient->get('recipient_type') === "character") {
                $getCharacter = $this->dataCont->getCharacter($recipient->get('recipient_id'));
                $status = $getCharacter->status;
                $payload = $getCharacter->payload;
                unset($status, $payload);
            }
            if ($recipient->get('recipient_type') === "corporation") {
                $getCorporation = $this->dataCont->getCorporation($recipient->get('recipient_id'));
                $status = $getCorporation->status;
                $payload = $getCorporation->payload;
                unset($status, $payload);
            }
            if ($recipient->get('recipient_type') === "alliance") {
                $getAlliance = $this->dataCont->getAlliance($recipient->get('recipient_id'));
                $status = $getAlliance->status;
                $payload = $getAlliance->payload;
                unset($status, $payload);
            }

            if ($recipient->get('recipient_type') === "mailing_list") {
                $getMailingList = MailingList::firstOrNew(['id' => $recipient->get('recipient_id')]);
                $header->fill(['is_on_mailing_list' => 1, 'mailing_list_id' => $recipient->get('recipient_id')]);
            }
        });

        $header->fill(['is_ready' => 1]);
        $header->save();
        return true;
    }
}
