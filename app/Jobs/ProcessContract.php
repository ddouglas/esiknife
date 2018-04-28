<?php

namespace ESIK\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use ESIK\Models\Member;
use ESIK\Http\Controllers\DataController;
use ESIK\Models\ESI\{Character, Contract, Corporation, Alliance, Station, Structure};
use ESIK\Jobs\ESI\{GetCharacter, GetCorporation, GetAlliance, GetStation, GetStructure, GetContractItems};

use Illuminate\Support\Collection;

class ProcessContract implements ShouldQueue
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
        $contract = $this->contract;
        $dbContract = Contract::find($this->contract->get('contract_id'));
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
                GetCharacter::dispatch($characterId)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
            $x = 0;
            $corporationIds->diff($knownCorporations->keys())->each(function ($corporationId) use (&$now, &$x) {
                GetCorporation::dispatch($corporationId)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
            $x = 0;
            $allianceIds->diff($knownAlliances->keys())->each(function ($allianceId) use (&$now, &$x) {
                GetAlliance::dispatch($allianceId)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
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
        $stationIds = $stationIds->unique()->values();
        $knownStructures = Structure::whereIn('id', $structureIds->toArray())->get()->keyBy('id');
        $x = 0;
        $structureIds->diff($knownStructures->keys())->each(function ($structureId) use (&$now, &$x) {
            if ($this->member->scopes->contains(config('services.eve.scopes.readUniverseStructures'))) {
                GetStructure::dispatch($this->member, $structureId)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            } else {
                Structure::create([
                    'id' => $structureId,
                    'name' => "Unknown Structure" . $structureId
                ]);
            }
        });

        $knownStations = Station::whereIn('id', $stationIds->toArray())->get()->keyBy('id');
        $x = 0;
        $stationIds->diff($knownStations->keys())->each(function ($stationId) use (&$now, &$x) {
            GetStation::dispatch($stationId)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });
        $dbContract->save();
        GetContractItems::dispatch($this->member, $this->contract)->delay($now->addSeconds(10));
    }
}
