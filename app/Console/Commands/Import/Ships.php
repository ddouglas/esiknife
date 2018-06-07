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
            dd("43 groups are suppose to be in the database to conduct this operation. Please run Import:SDE before runnig this command.");
        }

        $types = collect();
        $shipGroups->each(function ($group) use (&$types) {
            $groupRequest = $this->dataCont->getGroup($group->id);
            $status = $groupRequest->status;
            $payload = $groupRequest->payload;
            if (!$status) {
                return true;
            }
            $types = $types->merge(collect($payload->response->types));
        });
        $count = $types->count();
        $now = now(); $x = 1;
        $types->each(function ($type) use ($count, &$now, &$x) {
            $getType = $this->dataCont->getType($type);
            $status = $getType->status;
            $payload = $getType->payload;
            if (!$status) {
                dump($payload->message);
                return true;
            }
            if ($payload->wasRecentlyCreated) {
                dump($payload->name. " added to the database");
            }
            dump(round(($x / $count), 2) * 100 . "% complete. " . ($count - $x) . " records left");
            if ($x%5==0) {
                sleep(1);
            }
            $x++;
        });

    }
}
