<?php

namespace ESIK\Jobs;

use Bus;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use ESIK\Models\Member;
use ESIK\Traits\Trackable;
use ESIK\Http\Controllers\DataController;
use ESIK\Models\ESI\{Character, Contract, Corporation, Alliance, Station, Structure};
use ESIK\Jobs\ESI\{GetCharacter, GetCorporation, GetAlliance, GetStation, GetStructure, GetContractItems};

use Illuminate\Support\Collection;

class ProcessContract implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Trackable;

    public $memberId, $contract, $dataCont;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $memberId, string $contract)
    {
        $this->memberId = $memberId;
        $this->contract = $contract;
        $this->dataCont = new DataController;
        $this->prepareStatus();
        $this->setInput(['memberId' => $memberId, 'contract' => $contract]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $member = Member::findOrFail($this->memberId);
        $contract = collect(json_decode($this->contract, true));
        $dbContract = Contract::find($contract->get('contract_id'));
        if (is_null($dbContract)) {
            return false;
        }
        $now = now(); $x = 0;
        $entities = $contract->filter(function ($entity, $key) {
            if (in_array($key, ['issuer_id', 'issuer_corporation_id', 'assignee_id', 'acceptor_id']) && $entity != 0) {
                return true;
            }
        });
        $entities = $entities->unique()->values();
        $postEntities = $this->dataCont->postUniverseNames($entities);
        $pEStatus = $postEntities->status;
        $pEPayload = $postEntities->payload;
        if ($pEStatus) {
            $pEResponse = collect($pEPayload->response)->recursive()->keyBy('id');
            if ($pEResponse->has($dbContract->assignee_id)) {
                $dbContract->assignee_type = $pEResponse->get($dbContract->assignee_id)->get('category');
            }
            if ($pEResponse->has($dbContract->acceptor_id)) {
                $dbContract->acceptor_type = $pEResponse->get($dbContract->acceptor_id)->get('category');
            }

            $characterIds = $pEResponse->where('category', 'character')->pluck('id');
            $corporationIds = $pEResponse->where('category', 'corporation')->pluck('id');
            $allianceIds = $pEResponse->where('category', 'alliance')->pluck('id');

            $knownCharacters = Character::whereIn('id', $characterIds->toArray())->get()->keyBy('id');
            $knownCorporations = Corporation::whereIn('id', $corporationIds->toArray())->get()->keyBy('id');
            $knownAlliances = Alliance::whereIn('id', $allianceIds->toArray())->get()->keyBy('id');
            $x = 0;
            $characterIds->diff($knownCharacters->keys())->each(function ($characterId) use (&$now, &$x) {
                $class = \ESIK\Jobs\ESI\GetCharacter::class;
                $params = collect(['id' => $characterId]);
                $shouldDispatch = $this->shouldDispatchJob($class, $params);
                if ($shouldDispatch) {
                    $this->dataCont->getCharacter($characterId);
                }
                if ($x%10==0) {
                    usleep(50000);
                }
                $x++;
            });
            $x = 0;
            $corporationIds->diff($knownCorporations->keys())->each(function ($corporationId) use (&$now, &$x) {
                $class = \ESIK\Jobs\ESI\GetCorporation::class;
                $params = collect(['id' => $corporationId]);
                $shouldDispatch = $this->shouldDispatchJob($class, $params);
                if ($shouldDispatch) {
                    $this->dataCont->getCorporation($corporationId);
                }
                if ($x%10==0) {
                    sleep(1);
                }
                $x++;
            });
            $x = 0;
            $allianceIds->diff($knownAlliances->keys())->each(function ($allianceId) use (&$now, &$x) {
                $class = \ESIK\Jobs\ESI\GetAlliance::class;
                $params = collect(['id' => $allianceId]);
                $shouldDispatch = $this->shouldDispatchJob($class, $params);
                if ($shouldDispatch) {
                    $this->dataCont->getAlliance($allianceId);
                }
                if ($x%10==0) {
                    sleep(1);
                }
                $x++;
            });
        }
        $structureIds = collect(); $stationIds = collect();
        $locations = $contract->each(function ($entity, $key) use (&$structureIds, &$stationIds) {
            if (in_array($key, ['start_location_id', 'end_location_id']) && $entity != 0) {
                if ($entity >= 1000000000000) {
                    $structureIds->push($entity);
                } else {
                    $stationIds->push($entity);
                }
            }
        });
        $structureIds = $structureIds->unique()->values();
        $knownStructures = Structure::whereIn('id', $structureIds->toArray())->get()->keyBy('id');
        $x = 0;
        $structureIds->diff($knownStructures->keys())->each(function ($structureId) use ($member, &$now, &$x) {
            if ($member->scopes->contains(config('services.eve.scopes.readUniverseStructures'))) {
                $class = \ESIK\Jobs\ESI\GetStructure::class;
                $params = collect(['memberId' => $member->id, 'id' => $structureId]);
                $shouldDispatch = $this->shouldDispatchJob($class, $params);
                if ($shouldDispatch) {
                    $this->dataCont->getStructure($member, $structureId);
                }
                if ($x%10==0) {
                    sleep(1);
                }
                $x++;
            }
        });
        $stationIds = $stationIds->unique()->values();
        $knownStations = Station::whereIn('id', $stationIds->toArray())->get()->keyBy('id');
        $x = 0;
        $stationIds->diff($knownStations->keys())->each(function ($stationId) use (&$now, &$x) {
            $this->dataCont->getStation($stationId);
            $class = \ESIK\Jobs\ESI\GetStructure::class;
            $params = collect(['id' => $stationId]);
            $shouldDispatch = $this->shouldDispatchJob($class, $params);
            if ($shouldDispatch) {
                $this->dataCont->getStation($stationId);
            }
            if ($x%10==0) {
                sleep(1);
            }
            $x++;
        });
        $dbContract->save();
        $job = new GetContractItems($this->memberId, $contract->get('contract_id'));
        Bus::dispatch($job);
        $member->jobs()->attach($job->getJobStatusId());
    }
}
