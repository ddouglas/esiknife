<?php

namespace ESIK\Console\Commands\Import;

use ESIK\Models\SDE\{Group};
use ESIK\Models\ESI\{Type};
use ESIK\Jobs\ESI\{GetType};
use Illuminate\Console\Command;

use ESIK\Http\Controllers\DataController;

class Ships extends Command
{
    public $dataCont;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:ships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all ships, and their dogma attributes/effects into the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dataCont = new DataController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shipGroups = Group::where('category_id', 6)->where('published', 1)->get();
        if ($shipGroups->count() < 43) {
            dd("To few groups for operation");
        }
        $now = now(); $x = 0;
        $shipGroups->each(function ($group) use (&$now, &$x) {
            $groupRequest = $this->dataCont->getGroup($group->id);
            $status = $groupRequest->status;
            $payload = $groupRequest->payload;
            if (!$status) {
                return true;
            }
            $response = collect($payload->response)->recursive();
            $response->get('types')->each(function ($type) use (&$now, &$x) {
                GetType::dispatch($type)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
        });

    }
}
