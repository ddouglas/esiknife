<?php

namespace ESIK\Jobs\ESI;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use ESIK\Http\Controllers\DataController;

class GetType implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id, $dataCont;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->dataCont = new DataController();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $getType = $this->dataCont->getType($this->id);
        $status = $getType->status;
        $payload = $getType->payload;

        return $status;
    }
}
