<?php

namespace ESIK\Http\Controllers;

use Carbon, DOMDocument, Request, Session;

use ESIK\Jobs\ESI\{GetCharacter, GetCorporation, GetSystem, GetType, GetStation, GetStructure};
use ESIK\Models\{Member};
use ESIK\Models\ESI\{Alliance, Character, Corporation, Station, Structure, System, Type};

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

            $request = $this->httpCont->getCharactersCharacterIdCorporationHistory($id);
            if (!$request->status) {
                return $request;
            }
            $response = collect($request->payload->response)->recursive();
            $corporationIds = $response->pluck('corporation_id')->unique();
            $knownCorps = Corporation::whereIn('id', $corporationIds->toArray())->get()->keyBy('id');
            $now = now(); $x = 0;
            $corporationIds->diff($knownCorps->keys())->each(function ($corporation) use (&$now, &$x) {
                GetCorporation::dispatch($corporation)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });

            $response->each(function ($record) use ($character) {
                $character->corporationHistory()->updateOrCreate(['record_id' => $record->get('record_id')], [
                    'corporation_id' => $record->get('corporation_id'),
                    'is_deleted' => $record->has('is_deleted'),
                    'start_date' => Carbon::parse($record->get('start_date'))
                ]);
            });

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
    public function getMemberBookmarks (Member $member)
    {
        $request = $this->httpCont->getCharactersCharacterIdBookmarksFolders($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();
        $member->bookmarkFolders()->delete();
        $member->bookmarkFolders()->createMany($response->toArray());

        $request = $this->httpCont->getCharactersCharacterIdBookmarks($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();
        $itemTypeIds = $response->pluck('item.type_id')->unique()->reject(function ($item) {
            return is_null($item);
        })->values();

        $knownTypes = Type::whereIn('id', $itemTypeIds->toArray())->get()->keyBy('id');

        $now = now(); $x = 0;
        $knownTypes->keys()->diff($itemTypeIds)->each(function ($type) use (&$now, &$x){
            GetType::dispatch($type)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $locationIds = $response->pluck('location_id')->unique()->values();
        $knownSystems = System::whereIn('id', $locationIds->toArray())->get()->keyBy('id');

        $now = now(); $x = 0;
        $knownSystems->keys()->diff($locationIds)->each(function ($system) use (&$now, &$x){
            GetSystem::dispatch($system)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $creatorIds = $response->pluck('creator_id')->unique()->values();
        $knownCreators = Character::whereIn('id', $creatorIds->toArray())->get()->keyBy('id');
        $now = now(); $x = 0;
        $knownCreators->keys()->diff($creatorIds)->each(function ($character) use (&$now, &$x) {
            GetCharacter::dispatch($character)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $bookmarks = collect();
        $response->each(function ($bookmark) use ($bookmarks) {
            $bookmarks->put($bookmark->get('bookmark_id'), collect([
                'bookmark_id' => $bookmark->get('bookmark_id'),
                "folder_id" => $bookmark->get('folder_id'),
                "label" => $bookmark->get('label'),
                "notes" => $bookmark->get('notes'),
                "location_id" => $bookmark->get('location_id'),
                "creator_id" => $bookmark->get('creator_id'),
                "created" => Carbon::parse($bookmark->get('created')),
            ]));
            if ($bookmark->has('item')) {
                $bookmarks->get($bookmark->get('bookmark_id'))->put('item_id', $bookmark->get('item')->get('item_id'));
                $bookmarks->get($bookmark->get('bookmark_id'))->put('item_type_id', $bookmark->get('item')->get('type_id'));
            }
            if ($bookmark->has('coordinates')) {
                $bookmarks->get($bookmark->get('bookmark_id'))->put('coordinates', $bookmark->get('coordinates')->toJson());
            }
        });
        $member->bookmarks()->delete();
        $member->bookmarks()->createMany($bookmarks->toArray());

        return $request;
    }

    // Method from the Character Skils Namepsace

    public function getMemberClones(Member $member, Collection $scopes)
    {
        $request = $this->httpCont->getCharactersCharacterIdClones($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();
        $deathClone = $response->get('home_location');

        if ($deathClone->get('location_type') === "structure") {
            GetStructure::dispatch($member, $deathClone->get('location_id'));
        } elseif ($deathClone->get('location_type') === "station") {
            GetStation::dispatch($deathClone->get('location_id'));
        }

        $member->fill([
            'clone_location_id' => $deathClone->get('location_id'),
            'clone_location_type' => $deathClone->get('location_type')
        ]);
        $member->save();

        if ($response->get('jump_clones')->isNotEmpty()) {
            $jumpClones = collect();
            $response->get('jump_clones')->keyBy('jump_clone_id')->each(function ($clone) use ($member, $scopes, $jumpClones) {
                $member->jumpClones()->updateOrCreate(['clone_id' => $clone->get('jump_clone_id')], [
                    'location_id' => $clone->get('location_id'),
                    'location_type' => $clone->get('location_type'),
                    'implants' => $clone->get('implants')->toJson()
                ]);
                if ($clone->get('location_type') === "structure") {
                    $structure = Structure::firstOrNew(['id' => $clone->get('location_id')]);
                    if (!$structure->exists || $structure->name === "Unknown Structure ". $clone->get('location_id')) {
                        if ($scopes->contains('esi-universe.read_structures.v1')) {
                            GetStructure::dispatch($member, $clone->get('location_id'));
                        } else {
                            $structure->fill(['name' => "Unknown Structure " . $clone->get('location_id')]);
                            $structure->save();
                        }
                    }
                } elseif ($clone->get('location_type') === "station") {
                    $station = Station::firstOrNew(['id' => $response->get('station_id')]);
                    if (!$station->exists) {
                        GetStation::dispatch($response->get('station_id'));
                    }
                }

            });
        }
        return $request;
    }

    // Method from the Character Skils Namepsace

    /**
    * Fetches and Parses the current skills of the member
    *
    * @param ESIK\Models\Member $member Instance of Eloquent Member Model. This model contains the id and token we need to make the call.
    * @return mixed
    */
    public function getMemberLocation (Member $member, Collection $scopes)
    {
        $request = $this->httpCont->getCharactersCharacterIdLocation($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();

        $system = System::firstOrNew(['id' => $response->get('solar_system_id')]);
        if (!$system->exists) {
            GetSystem::dispatch($response->get('solar_system_id'));
        }

        // Query System ID for System Dat was successful, Set Member Location Data
        $location = collect([
            "solar_system_id" => $system->id,
            "location_id" => null,
            "location_type" => null
        ]);

        // Does the property station_data exists? If yes, character is currently in a Station or Outpost.
        if ($response->has('station_id')) {
            $station = Station::firstOrNew(['id' => $response->get('station_id')]);
            if (!$station->exists) {
                GetStation::dispatch($response->get('station_id'));
            }
            $location->put('location_id', $response->get('station_id'));
            $location->put('location_type', "station");

        } else if ($response->has('structure_id')) {
            $structure = Structure::firstOrNew(['id' => $response->get('structure_id')]);
            if (!$structure->exists || $structure->name === "Unknown Structure ". $response->get('structure_id')) {
                if ($scopes->contains('esi-universe.read_structures.v1')) {
                    GetStructure::dispatch($member, $response->get('structure_id'));
                } else {
                    $structure->fill(['name' => "Unknown Structure " . $structure->id]);
                    $structure->save();
                }
            }
            $location->put('location_id', $response->get('structure_id'));
            $location->put('location_type', "structure");
        } else {
            $location->put('location_id', $response->get('solar_system_id'));
            $location->put('location_type', "system");
        }
        $member->location()->updateOrCreate([], $location->toArray());

        return $request;
    }

    /**
    * Fetches and Parses the current skills of the member
    *
    * @param ESIK\Models\Member $member Instance of Eloquent Member Model. This model contains the id and token we need to make the call.
    * @return mixed
    */
    public function getMemberShip (Member $member)
    {
        $request = $this->httpCont->getCharactersCharacterIdShip($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();
        $shipTypeInfo = Type::where('id', $response->get('ship_type_id'))->first();
        if (is_null($shipTypeInfo)) {
            GetType::dispatch($response->get('ship_type_id'));
        }

        $member->ship()->updateOrCreate([], [
            'type_id' => $response->get('ship_type_id'),
            'item_id' => $response->get('ship_item_id'),
            'name' => $response->get('ship_name')
        ]);

        return $request;
    }

    // Method from the Character Skils Namepsace

    /**
    * Fetches and Parses the current skills of the member
    *
    * @param ESIK\Models\Member $member Instance of Eloquent Member Model. This model contains the id and token we need to make the call.
    * @return mixed
    */
    public function getMemberSkillz (Member $member)
    {
        $request = $this->httpCont->getCharactersCharacterIdSkillz($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();
        $skills = $response->get('skills')->keyBy('skill_id');
        $skillIds = $skills->keys();
        $knownTypes = Type::whereIn('id', $skillIds->toArray())->get()->keyBy('id');
        $now = now(); $x = 0;
        $skills->diffKeys($knownTypes)->each(function ($skill) use (&$now, &$x){
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

    public function getMemberSkillqueue(Member $member)
    {
        $request = $this->httpCont->getCharactersCharacterIdSkillqueue($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive()->keyBy('queue_position');
        $skillIds = $response->pluck('skill_id')->unique()->values();
        $knownTypes = Type::whereIn('id', $skillIds->toArray())->get();
        $knownTypeIds = $knownTypes->pluck('id');

        $now = now(); $x = 0;
        $skillIds->diff($knownTypeIds)->each(function ($skill) use (&$now, &$x){
            GetType::dispatch($skill)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $skillQueue = collect();

        $response->each(function ($queue_item) use ($skillQueue) {
            if (property_exists($queue_item, 'finish_data') && Carbon::parse($queue_item->finish_date)->lt(Carbon::now())) {
                return true;
            }
            $skillQueue->put($queue_item->get('queue_position'), collect([
                'skill_id' => $queue_item->get('skill_id'),
                'queue_position' => $queue_item->get('queue_position'),
                'finished_level' => $queue_item->get('finished_level'),
                'level_start_sp' => $queue_item->get('level_start_sp'),
                'level_end_sp' => $queue_item->get('level_end_sp'),
                'training_start_sp' => $queue_item->get('training_start_sp'),
                'start_date' => $queue_item->has('start_date') ? Carbon::parse($queue_item->get('start_date')) : null,
                'finish_date' => $queue_item->has('finish_date') ? Carbon::parse($queue_item->get('finish_date')) : null
            ]));
        });

        $member->skillQueue()->detach();
        $member->skillQueue()->attach($skillQueue->toArray());

        return $request;
    }

    // Methods from the Character Wallet Namespace

    /**
    * Fetches the current balance of the characters wallet
    *
    * @param ESIK\Models\Member $member Instance of Eloquent Member Model. This model contains the id and token we need to make the call.
    * @return mixed
    */
    public function getMemberWallet(Member $member)
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
                if (!$structure->exists) {
                    $structure->fill(['name' => "Unknown Structure " . $structure->id]);
                    $structure->save();
                }
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
            ]);
            $structure->save();
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
