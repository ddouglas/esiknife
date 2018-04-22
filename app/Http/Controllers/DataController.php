<?php

namespace ESIK\Http\Controllers;

use Carbon, DOMDocument, Request, Session;

use ESIK\Jobs\ESI\{GetType};
use ESIK\Models\ESI\{Character, Corporation, Alliance, Type};

use Illuminate\Support\Collection;

class DataController extends Controller
{
    public $httpCont;

    public function __construct()
    {
        $this->httpCont = new HttpController;
    }

    /**
    * Makes an HTTP GET request to CCP SSO with an Authorization Code to verify the code is valid
    *
    * @param string $code Authorization Code received in the callback from CCP
    * @return mixed Returns an object from the HttpController containing the response payload and a status property.
    */
    public function verifyAuthCode(string $code)
    {
        $payload = collect([
            "cid" => config("services.eve.sso.id"),
            "cs" => config("services.eve.sso.secret"),
            "c" => $code
        ]);
        return $this->httpCont->oauthVerifyAuthCode($payload);
    }

    /**
    * Makes an HTTP GET request to ESI's verify endpoint to verify an access token.
    *
    * @param string $token Access Token from CCP
    * @return mixed Returns an object from the HttpController containing the response payload and a status property.
    */
    public function verifyAccessToken(string $token)
    {
        return $this->httpCont->oauthVerifyAccessToken($token);
    }

    /**
    * Retreive the current public information, corporation, and if applicable, alliance that a character is in.
    *
    * @param int $id The Character ID of the character in question
    * @return mixed
    */
    public function getMemberData(int $id)
    {
        $data = collect();
        //Member ID is valid, Make a request ESI /characters/{character_id} to grab some additional character info.
        $getCharacter = $this->getCharacter($id);
        if (!$getCharacter->status) {
            return $getCharacter;
        }
        //Request for Additional Member Data was successful. Let break it down and store what we need an a property.
        $data = $data->merge(collect($getCharacter->payload->getAttributes())->forget('cached_until')->forget('created_at')->forget('updated_at'));

        //No need to verify that the corporation ID is valid since we are using the value that we received from CCP.
        //Here we are requesting information about the corporation the character is in.
        $getCorporation = $this->getCorporation($data->get('corporation_id'));
        if (!$getCorporation->status) {
            return $getCorporation;
        }
        //The request for corporation information was successful. Lets store what we need and move on.
        $data->put('corporation', collect($getCorporation->payload->getAttributes())->forget('cached_until')->forget('created_at')->forget('updated_at'));
        if ($data->get('corporation')->has('alliance_id') && !is_null($data->get('corporation')->get('alliance_id'))) {
            $data->put('alliance_id', $data->get('corporation')->get('alliance_id'));
            $getAlliance = $this->getAlliance($data->get('alliance_id'));
            if (!$getAlliance->status) {
                return $getAlliance;
            }
            $data->put('alliance', collect($getAlliance->payload->getAttributes())->forget('cached_until')->forget('created_at')->forget('updated_at'));
        }
        return (object)[
            'status' => true,
            'payload' => (object)$data->toArray()
        ];
    }

    /**
    * For an ID, query ESI /characters/{character_id} for character data and return.
    *
    * @param int $id The id of the Character to query ESI for.
    * @return mixed
    **/
    public function getCharacter($id)
    {
        $character = Character::firstOrNew(['id' => $id]);
        if (!$character->exists || $character->cached_until < Carbon::now()) {
            $request = $this->httpCont->getCharactersCharacterId($id);
            if (!$request->status) {
                return $request;
            }
            $response = $request->payload->response;

            $responseHeaders = $request->payload->headers->response;
            $data = [
                'name' => $response->name,
                'birthday' => Carbon::parse($response->birthday),
                'gender' => $response->gender,
                'ancestry_id' => $response->ancestry_id,
                'bloodline_id' => $response->bloodline_id,
                'race_id' => $response->race_id,
                'sec_status' => $response->security_status,
                'bio' => $response->description,
                'corporation_id' => $response->corporation_id,
                'cached_until' => isset($responseHeaders['Expires']) ? Carbon::parse($responseHeaders['Expires'])->toDateTimeString() : Carbon::now()->addHour()->toDateTimeString()
            ];
            if (property_exists($response, 'alliance_id')) {
                $data['alliance_id'] = $response->alliance_id;
            } else {
                $data['alliance_id'] = null;
            }
            $character->fill($data);
            $character->save();
        }
        return (object)[
            'status' => true,
            'payload' => $character
        ];
    }

    /**
    * For an ID, query ESI /corporations/{corporation_id} for corporation data and return.
    *
    * @param int $id The id of the Corporation to query ESI for.
    * @return mixed
    **/
    public function getCorporation(int $id)
    {
        $corporation = Corporation::firstOrNew(["id" => $id]);
        if (!$corporation->exists || $corporation->cached_until < Carbon::now()) {
            $request = $this->httpCont->getCorporationsCorporationId($id);
            if (!$request->status) {
                return $request;
            }
            $response = $request->payload->response;
            $data = [
                'name' => $response->name,
                'ticker' => $response->ticker,
                'member_count' => $response->member_count,
                'ceo_id' => $response->ceo_id,
                'creator_id' => $response->creator_id,
                'home_station_id' => $response->home_station_id,
                'description' => $response->description,
                'cached_until' => isset($responseHeaders['Expires']) ? Carbon::parse($responseHeaders['Expires'])->toDateTimeString() : Carbon::now()->addHour()->toDateTimeString()
            ];
            if (property_exists($response, 'alliance_id')) {
                $data['alliance_id'] = $response->alliance_id;
            } else {
                $data['alliance_id'] = null;
            }
            if (property_exists($response, 'date_founded')) {
                $data['date_founded'] = Carbon::parse($response->date_founded)->toDateTimeString();
            }
            $corporation->fill($data);
            $corporation->save();
        }
        return (object)[
            'status' => true,
            'payload' => $corporation
        ];
    }

    /**
    * For an ID, query ESI /alliances/{alliance_id} for alliance data and return.
    *
    * @param int $id The id of the Alliance to query ESI for.
    * @return mixed
    **/
    public function getAlliance(int $id)
    {
        $alliance = Alliance::firstOrNew(['id' => $id]);
        if (!$alliance->exists || $alliance->cached_until < Carbon::now()) {
            $request = $this->httpCont->getAlliancesAllianceId($id);
            if (!$request->status) {
                return $request;
            }
            $response = $request->payload->response;
            $alliance->fill([
                'name' => $response->name,
                'ticker' => $response->ticker,
                'creator_id' => $response->creator_id,
                'creator_corporation_id' =>$response->creator_corporation_id,
                'executor_corporation_id' => $response->executor_corporation_id,
                'cached_until' => isset($responseHeaders['Expires']) ? Carbon::parse($responseHeaders['Expires'])->toDateTimeString() : Carbon::now()->addHour()->toDateTimeString()
            ]);
            $alliance->save();
        }
        return (object)[
            'status' => true,
            'payload' => $alliance
        ];
    }

    // Method from the Character Skils Namepsace

    /**
    * Fetches and Parses the current skills of the member
    *
    * @param ESIK\Models\Member $member Instance of Eloquent Member Model. This model contains the id and token we need to make the call.
    * @return mixed
    */
    public function getMemberSkillz ($member)
    {
        $request = $this->httpCont->getCharactersCharacterIdSkillz($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();
        $skills = $response->get('skills')->recursive()->keyBy('skill_id');
        $skillIds = $skills->keys();
        $knownTypes = Type::whereIn('id', $skillIds->toArray())->get()->keyBy('id');

        $diff = $skills->diffKeys($knownTypes);

        $now = now(); $x = 0;
        $diff->each(function ($skill) use (&$now, &$x){
            GetType::dispatch($skill->get('skill_id'))->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $member->skillz()->detach();
        $member->skillz()->attach($skills->toArray());
        $member->total_sp = $response->get('total_sp');
        $member->save();

        return $request;
    }

    public function getMemberSkillqueue($member)
    {
        $request = $this->httpCont->getCharactersCharacterIdSkillqueue($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive()->keyBy('queue_position');
        dump($response);
        abort(200);
        $skillqueue = collect();

        foreach($response as $queue_item) {
            $type = Type::firstOrNew(['id' => $queue_item->skill_id]);
            if (property_exists($queue_item, 'finish_date')) {
                if (Carbon::parse($queue_item->finish_date)->lt(Carbon::now())) {
                    continue;
                }
            }
            if (!$type->exists) {
                GetType::dispatch($queue_item->skill_id);
            }
            $skillqueue->put($queue_item->queue_position, collect([
                'type_id' => $queue_item->skill_id,
                'queue_position' => $queue_item->queue_position,
                'finished_level' => $queue_item->finished_level,
                'starting_sp' => $queue_item->level_start_sp,
                'finishing_sp' => $queue_item->level_end_sp,
                'training_start_sp' => $queue_item->training_start_sp,
                'start_date' => property_exists($queue_item, 'start_date') ? $queue_item->start_date : null,
                'finish_date' => property_exists($queue_item, 'finish_date') ? $queue_item->finish_date : null
            ]));
        }

        $member->skill_queue()->detach();
        $member->skill_queue()->attach($skillqueue->toArray());

        return $request;
    }

    // Methods from the Character Wallet Namespace

    /**
    * Fetches the current balance of the characters wallet
    *
    * @param ESIK\Models\Member $member Instance of Eloquent Member Model. This model contains the id and token we need to make the call.
    * @return mixed
    */
    public function getMemberWallet($member)
    {
        $request = $this->httpCont->getCharactersCharacterIdWallet($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }

        $member->wallet_balance = $request->payload->response;
        $member->save();

        return $request;
    }


    // ****************************************************************************************************
    // ****************************** Methods Regarding the EVE Universe  ********************************
    // ****************************************************************************************************

    /**
    * Queries Database to see if the structure exists, if it doesn't a GET HTTP Request is made to ESI /universe/structure/{structure_id} to get the structure data
    *
    * @param ESIK\Models\Member $member Member to use when performing query
    * @param int $id ID of the station/outpost to retrieve data for.
    *
    * @return mixed
    **/
    public function getStructure(Member $member, int $id)
    {
        $structure = Structure::firstOrNew(['id' => $id]);
        if (!$structure->exists || $structure->cached_until < Carbon::now()) {
            $request = $this->httpCont->getUniverseStructuresStructureId($id, $member->access_token);
            if (!$request->status) {
                $structure->fill(['name' => "Unknown Structure " . $structure->id])->save();
                return $request;
            }
            $response = $request->payload->response;
            $structure->fill([
                'name' => $response->name,
                'solar_system_id' => $response->solar_system_id,
                'pos_x' => $response->position->x,
                'pos_y' => $response->position->y,
                'pos_z' => $response->position->z,
                'cached_until' => isset($responseHeaders['Expires']) ? Carbon::parse($responseHeaders['Expires'])->toDateTimeString() : Carbon::now()->addHour()->toDateTimeString()
            ])->save();
        }
        return (object)[
            'status' => true,
            'payload' => $structure
        ];
    }

    /**
    * Queries Database to see if the station exists, if it doesn't a GET HTTP Request is made to ESI /universe/stations/{station_id} to get the station data
    *
    * @param int $id ID of the station/outpost to retrieve data for.
    *
    * @return mixed
    **/
    public function getStation(int $id)
    {
        $station = Station::firstOrNew(['id' => $id]);
        if (!$station->exists || $station->cached_until < Carbon::now()) {
            $request = $this->httpCont->getUniverseStationsStationId($id);
            if (!$request->status) {
                return $request;
            }
            $response = $request->payload->response;
            $station->fill([
                'name' => $response->name,
                'system_id' => $response->system_id,
                'owner_id' => $response->owner,
                'type_id' => $response->type_id,
                'pos_x' => $response->position->x,
                'pos_y' => $response->position->y,
                'pos_z' => $response->position->z,
                'cached_until' => isset($responseHeaders['Expires']) ? Carbon::parse($responseHeaders['Expires'])->toDateTimeString() : Carbon::now()->addHour()->toDateTimeString()
            ])->save();
        }
        return (object)[
            'status' => true,
            'payload' => $station
        ];
    }

    /**
    * Queries Database to see if the system exists, if it doesn't a GET HTTP Request is made to ESI /universe/systems/{system_id} to get the system data
    *
    * @param int $id ID of the system to retrieve data for.
    *
    * @return mixed
    **/
    public function getSystem(int $id)
    {
        $system = System::firstOrNew(['id' => $id]);
        if (!$system->exists || $system->cached_until < Carbon::now()) {
            $request = $this->httpCont->getUniverseSystemsSystemId($id);
            if (!$request->status) {
                return $request;
            }
            $response = $request->payload->response;
            $system->fill([
                'name' => $response->name,
                'star_id' => $response->star_id,
                'pos_x' => $response->position->x,
                'pos_y' => $response->position->y,
                'pos_z' => $response->position->z,
                'security_status' => $response->security_status,
                'constellation_id' => $response->constellation_id,
                'cached_until' => isset($responseHeaders['Expires']) ? Carbon::parse($responseHeaders['Expires'])->toDateTimeString() : Carbon::now()->addHour()->toDateTimeString()
            ]);
            $system->save();
        }
        return (object)[
            'status' => true,
            'payload' => $system
        ];
    }

    /**
    * Queries ESI /latest/universe/ids
    *
    *   @param Illuminate\Support\Collection $names An Array of Names to Convert to Ids
    **/
    public function postUniverseIds(Collection $names)
    {
        return $this->httpCont->postUniverseIds($names);
    }

    /**
    * Queries ESI /latest/universe/names
    *
    *   @param Illuminate\Support\Collection $names An Array of Names to Convert to Ids
    **/
    public function postUniverseNames(Collection $ids)
    {
        return $this->httpCont->postUniverseNames($ids->toArray());
    }

    public function getStatus()
    {
        $request = $this->httpCont->getStatus();
        if (!$request->status) {
            return $request;
        }
        return (object)[
            'status' => true,
            'payload' => $request->payload->response
        ];
    }

    public function getType($id, $fresh=false)
    {
        $type = Type::firstOrNew(['id'=>$id]);
        if (!$type->exists || $type->attributes->isEmpty() || $type->effects->isEmpty()) {
            $request = $this->httpCont->getUniverseTypesTypeId($id);
            if (!$request->status) {
                return $request;
            }
            $response = $request->payload->response;
            $type->fill([
                'name' => $response->name,
                'description' => $response->description,
                'published' => $response->published,
                'group_id' => $response->group_id,
                'volume' => $response->volume
            ]);
            $type->save();

            if (property_exists($response, 'dogma_effects')) {
                $effects = collect($response->dogma_effects)->recursive();
                $dbEffects = $type->effects()->whereIn('effect_id', $effects->pluck('effect_id')->toArray())->get()->keyBy('effect_id');
                $effects->each(function ($effect) use ($dbEffects, $type) {
                    if (!$dbEffects->has($effect->get('effect_id'))) {
                        $type->effects()->create($effect->toArray());
                    }
                });
            }
            if (property_exists($response, 'dogma_attributes')) {
                $attributes = collect($response->dogma_attributes)->recursive();
                $dbAttributes = $type->attributes()->whereIn('attribute_id', $attributes->pluck('attribute_id')->toArray())->get()->keyBy('attribute_id');
                $attributes->each(function ($attribute) use ($dbAttributes, $type) {
                    if (!$dbAttributes->has($attribute->get('attribute_id'))) {
                        $type->attributes()->create($attribute->toArray());
                    }
                });

                $typeDogma = $attributes->whereIn('attribute_id', config('base.defaults.dogma.skillz.all'))->keyBy('attribute_id');
                $typeSkillz = collect();
                collect(config('base.defaults.dogma.skillz.mapping'))->each(function ($level, $skill) use ($typeDogma, $typeSkillz) {
                    if ($typeDogma->has($skill) && $typeDogma->has($level)) {
                        $skillId = (int)$typeDogma->get($skill)->get('value');
                        $skillLvl = (int)$typeDogma->get($level)->get('value');
                        $dogmaSkill = Type::firstOrNew(['id' => $skillId]);
                        if (!$dogmaSkill->exists) {
                            GetType::dispatch($skillId);
                        }
                        $typeSkillz->push(collect([
                            'id' => $skillId,
                            'value' => $skillLvl
                        ]));
                    }
                });
                if ($typeSkillz->isNotEmpty()) {
                    $type->skillz()->detach();
                    $type->skillz()->attach($typeSkillz->pluck('id')->toArray());
                }
            }
        }
        $type->load('group');
        return (object)[
            'status' => true,
            'payload' => $type
        ];
    }

    // Methods related to importing the SDE from zzeve
    public function getChrAncestries()
    {
        return $this->httpCont->getChrAncestries();
    }

    public function getChrBloodlines()
    {
        return $this->httpCont->getChrBloodlines();
    }

    public function getChrRaces()
    {
        return $this->httpCont->getChrRaces();
    }

    public function getInvCategories()
    {
        return $this->httpCont->getInvCategories();
    }

    public function getInvGroups()
    {
        return $this->httpCont->getInvGroups();
    }

    public function getMapConstellations()
    {
        return $this->httpCont->getMapConstellations();
    }

    public function getMapRegions()
    {
        return $this->httpCont->getMapRegions();
    }
}
