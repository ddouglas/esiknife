<?php

namespace ESIK\Http\Controllers;

use Carbon, DOMDocument, Request, Session;

use ESIK\Jobs\{ProcessContract,ProcessMailHeader};
use ESIK\Jobs\ESI\{GetCharacter, GetCorporation, GetAlliance, GetSystem, GetType, GetStation, GetStructure};
use ESIK\Models\{Member};
use ESIK\Models\ESI\{Alliance, Character, Contract, Corporation, MailHeader, MailingList, Station, Structure, System, Type};
use ESIK\Models\SDE\{Region, Constellation};

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
        return $this->httpCont->oauthVerifyAuthCode($code);
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
        $status = $request->status;
        $payload = $request->payload;
        if (!$status) {
            return $request;
        }
        $response = collect($payload->response)->recursive();
        $member->bookmarkFolders()->delete();
        $member->bookmarkFolders()->createMany($response->toArray());

        unset($status, $payload);

        $request = $this->httpCont->getCharactersCharacterIdBookmarks($member->id, $member->access_token);
        $status = $request->status;
        $payload = $request->payload;
        if (!$status) {
            return $request;
        }
        $bookmarks = collect($payload->response)->recursive();
        unset($status, $payload);
        $itemTypeIds = $bookmarks->pluck('item.type_id')->unique()->reject(function ($item) {
            return is_null($item);
        })->values();
        $locationIds = $bookmarks->pluck('location_id')->unique()->values();
        $creatorIds = $bookmarks->pluck('creator_id')->unique()->values();


        $ids = $itemTypeIds->merge($locationIds)->merge($creatorIds);

        $request = $this->postUniverseNames($ids);
        $status = $request->status;
        $payload = $request->payload;
        if (!$status) {
            return $request;
        }

        $dictionary = collect($payload->response)->recursive()->keyBy('id');

        $now = now(); $x = 0;
        $characterIds = $dictionary->where('category', 'character')->pluck('id');
        if ($characterIds->isNotEmpty()) {
            $knownCharacters = Character::whereIn('id', $characterIds->toArray())->get()->keyBy('id');
            $characterIds->diff($knownCharacters->keys())->each(function ($character) use (&$now, &$x) {
                GetCharacter::dispatch($character)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
        }

        $corporationIds = $dictionary->where('category', 'corporation')->pluck('id');
        if ($corporationIds->isNotEmpty()) {
            $knownCorporations = Corporation::whereIn('id', $corporationIds->toArray())->get()->keyBy('id');
            $corporationIds->diff($knownCorporations->keys())->each(function ($corporation) use (&$now, &$x) {
                GetCorporation::dispatch($corporation)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
        }

        $systemIds = $dictionary->where('category', 'solar_system')->pluck('id');
        if ($systemIds->isNotEmpty()) {
            $knownSystems = System::whereIn('id', $systemIds->toArray())->get()->keyBy('id');
            $systemIds->diff($knownSystems->keys())->each(function ($system) use (&$now, &$x) {
                GetSystem::dispatch($system)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
        }

        $memBookmarks = collect();
        $bookmarks->each(function ($bookmark) use ($memBookmarks,$dictionary) {
            if (is_null($dictionary->get($bookmark->get('location_id')))) {
                return true;
            }
            $memBookmarks->put($bookmark->get('bookmark_id'), collect([
                'bookmark_id' => $bookmark->get('bookmark_id'),
                "folder_id" => $bookmark->get('folder_id'),
                "label" => $bookmark->get('label'),
                "notes" => $bookmark->get('notes'),
                "location_id" => $bookmark->get('location_id'),
                "location_type" => $dictionary->get($bookmark->get('location_id'))->get('category'),
                "creator_id" => $bookmark->get('creator_id'),
                "creator_type" => $dictionary->get($bookmark->get('creator_id'))->get('category'),
                "created" => Carbon::parse($bookmark->get('created')),
            ]));
            if ($bookmark->has('item')) {
                $memBookmarks->get($bookmark->get('bookmark_id'))->put('item_id', $bookmark->get('item')->get('item_id'));
                $memBookmarks->get($bookmark->get('bookmark_id'))->put('item_type_id', $bookmark->get('item')->get('type_id'));
            }
            if ($bookmark->has('coordinates')) {
                $memBookmarks->get($bookmark->get('bookmark_id'))->put('coordinates', $bookmark->get('coordinates')->toJson());
            }
        });
        $member->bookmarks()->delete();
        $member->bookmarks()->createMany($memBookmarks->toArray());
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
                    $station = Station::firstOrNew(['id' => $clone->get('location_id')]);
                    if (!$station->exists) {
                        GetStation::dispatch($clone->get('location_id'));
                    }
                }
            });
        }
        return $request;
    }

    public function getMemberImplants(Member $member, Collection $scopes)
    {
        $request = $this->httpCont->getCharactersCharacterIdImplants($member->id, $member->access_token);

        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->recursive();

        $knownImplants = Type::whereIn('id', $response->toArray())->get()->keyBy('id');

        $now = now(); $x = 0;
        $response->diff($knownImplants->keys())->each(function ($type) use (&$now, &$x) {
            GetType::dispatch($type)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $member->implants()->detach();
        $member->implants()->attach($response->toArray());
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

    public function getMemberContacts(Member $member)
    {
        $labelRequest = $this->httpCont->getCharactersCharacterIdContactLabels($member->id, $member->access_token);
        $status = $labelRequest->status;
        $payload = $labelRequest->payload;
        if (!$status) {
            return $labelRequest;
        }
        $response = collect($payload->response)->recursive();
        $member->contact_labels()->delete();
        $member->contact_labels()->createMany($response->toArray());
        unset($status, $payload);
        $contactRequest = $this->httpCont->getcharactersCharacterIdContacts($member->id, $member->access_token);
        $status = $contactRequest->status;
        $payload = $contactRequest->payload;
        if (!$status) {
            return $contactRequest;
        }
        $contacts = collect();
        $response = collect($payload->response)->recursive()->each(function ($contact) use ($contacts) {
            $contacts->push([
                'contact_id' => $contact->get('contact_id'),
                'contact_type' => $contact->get('contact_type'),
                'standing' => $contact->get('standing'),
                'is_watched' => $contact->get('is_watched'),
                'is_blocked' => $contact->get('is_blocked'),
                'label_ids' => $contact->has('label_ids') ? $contact->get('label_ids')->toJson() : null
            ]);
        });
        $nonfaction = $contacts->whereIn('contact_type', ['character', 'corporation', 'alliance']);
        if ($nonfaction->isNotEmpty()) {
            $characterIds = $nonfaction->where('contact_type', 'character')->pluck('contact_id');
            $knownCharacters = Character::whereIn('id', $characterIds->toArray())->get()->keyBy('id');
            $now = now(); $x = 0;
            $characterIds->diff($knownCharacters->keys())->each(function ($characterId) use (&$now, &$x) {
                GetCharacter::dispatch($characterId)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });

            $corporationIds = $nonfaction->where('contact_type', 'corporation')->pluck('contact_id');
            $knownCorporations = Corporation::whereIn('id', $corporationIds->toArray())->get()->keyBy('id');
            $corporationIds->diff($knownCorporations->keys())->each(function ($corporationId) use (&$now, &$x) {
                GetCorporation::dispatch($corporationId)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });

            $allianceIds = $nonfaction->where('contact_type', 'alliance')->pluck('contact_id');
            $knownAlliances = Alliance::whereIn('id', $allianceIds->toArray())->get()->keyBy('id');
            $allianceIds->diff($knownAlliances->keys())->each(function ($allianceIds) use (&$now, &$x) {
                GetAlliance::dispatch($allianceIds)->delay($now);
                if ($x%10==0) {
                    $now->addSecond();
                }
                $x++;
            });
        }
        $member->contacts()->delete();
        $member->contacts()->createMany($contacts->toArray());
        return $contactRequest;
    }

    public function getMemberContracts(Member $member)
    {
        $getMemberContracts = $this->httpCont->getCharactersCharacterIdContracts($member->id, $member->access_token);
        $status = $getMemberContracts->status;
        $contractsPayload = $getMemberContracts->payload;
        if (!$status) {
            return $getMemberContracts;
        }
        $contracts = collect($contractsPayload->response)->recursive()->keyBy('contract_id');

        $contractIds = $contracts->keys();
        $knownContracts = Contract::whereIn('id', $contractIds)->get()->keyBy('id')->each(function ($knownContract) use ($contracts) {
            $contract = $contracts->get($knownContract->id);
            if ($knownContract->status !== $contract->get('status')) {
                $knownContract->status = $contract->get('status');
                $knownContract->save();
            }
        });
        $now = now(); $x=0;
        $contractIds->diff($knownContracts->keys())->each(function ($contractId) use ($member, $contracts, &$now, &$x) {
            $contract = $contracts->get($contractId);
            $create = Contract::create([
                'id' => $contractId,
                'issuer_id' => $contract->get('issuer_id'),
                'issuer_corporation_id' => $contract->get('issuer_corporation_id'),
                'assignee_id' => $contract->get('assignee_id'),
                'assignee_type' => null,
                'acceptor_id' => $contract->get('acceptor_id'),
                'acceptor_type' => null,
                'title' => $contract->get('title'),
                'type' => $contract->get('type'),
                'status' => $contract->get('status'),
                'availability' => $contract->get('availability'),
                'for_corporation' => $contract->get('for_corporation'),
                'days_to_complete' => $contract->get('days_to_complete'),
                'collateral' => $contract->get('collateral'),
                'price' => $contract->get('price'),
                'reward' => $contract->get('reward'),
                'volume' => $contract->get('volume'),
                'start_location' => $contract->get('start_location_id'),
                'start_location_type' => $contract->get('start_location_id') > 1000000000000 ? 'structure' : 'station',
                'end_location' => $contract->get('end_location_id'),
                'end_location_type' => $contract->get('end_location_id') > 1000000000000 ? 'structure' : 'station',
                'date_accepted' => $contract->has('date_accepted') ? Carbon::parse($contract->get('date_accepted')) : null,
                'date_completed' => $contract->has('date_completed') ? Carbon::parse($contract->get('date_completed')) : null,
                'date_expired' => $contract->has('date_expired') ? Carbon::parse($contract->get('date_expired')) : null,
                'date_issued' => $contract->has('date_issued') ? Carbon::parse($contract->get('date_issued')) : null
            ]);
            if ($create) {
                ProcessContract::dispatch($member, $contract)->delay($now);
            }
        });
        $member->contracts()->attach($contractIds->diff($knownContracts->keys())->toArray());
        return $getMemberContracts;
    }

    public function getMemberContractItems(Member $member, int $contractId)
    {
        $request = $this->httpCont->getCharactersCharacterIdContractsContractIdItems($member->id, $member->access_token, $contractId);
        $status = $request->status;
        $payload = $request->payload;
        if (!$status) {
            return $request;
        }
        $response = collect($payload->response)->recursive();
        $response->pluck('type_id')->unique()->values()->each(function ($type) {
            $getType = $this->getType($type);
        });

        $contract = Contract::find($contractId);
        if (is_null($contract)) {
            return (object)[
                'status' => false
            ];
        }
        $contract->items()->attach($response);
        return $request;
    }

    public function getMemberMailBody($member, $mailId)
    {
        $header = MailHeader::firstOrNew(['id' => $mailId]);
        if ($header->body === null) {
            $request = $this->httpCont->getCharactersCharacterIdMailMailId($member->id, $member->access_token, $mailId);
            if (!$request->status) {
                return $request;
            }
            $mailBody = $request->payload->response->body;
            $dom = new DOMDocument('1.0');
            libxml_use_internal_errors(true);

            $tidy = tidy_parse_string("<pre>".$mailBody."</pre>", [
                'doctype' => "omit",
                'show-body-only' => true,
                'wrap' => 0,
            ]);
            $mailBody = $tidy->value;
            if (is_null($mailBody) || empty($mailBody)) {
                die();
            }
            $mailBody = str_replace("<pre>", "", $mailBody);
            $mailBody = str_replace("</pre>", "", $mailBody);
            $dom->loadHtml(mb_convert_encoding($mailBody, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            foreach ($dom->getElementsByTagName('font') as $anchor) {
                if ($anchor->hasAttribute('size')) {
                    $anchor->removeAttribute('size');
                }
                if ($anchor->hasAttribute('color')) {
                    $anchor->removeAttribute('color');
                }
            }

            $domAnchors = $dom->getElementsByTagName('a');
            foreach ($domAnchors as $anchor) {
                $href = explode(':', $anchor->getAttribute('href'));
                if (!in_array($href[0], ['showinfo', 'fitting', 'killReport'])) {
                    continue;
                }
                $type = $href[0];
                $anchor->setAttribute('target', "_blank");
                if ($type === "showinfo") {
                    $target = $href[1];
                    $target = explode("//", $href[1]);
                    if (count($target) == 1) {
                        // Show Info on an Item;
                        $anchor->removeAttribute('href');
                        $anchor->setAttribute('href', config('base.kmUrl'). "type/{$target[0]}");
                        continue;
                    }

                    $targetType = (int)$target[0];
                    $targetId = (int)$target[1];

                    if ($targetType == 3) {
                        $info = Region::find($targetId);
                        if (is_null($info)) {
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.kmUrl'). "region/{$targetId}");
                        } else {
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.dotlanUrl'). "map/". $info->nameDotlanFormat());
                        }
                        continue;
                    }

                    if ($targetType == 4) {
                        $info = Constellation::find($targetId);
                        if (is_null($info)) {
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.kmUrl'). "/constellation/{$targetId}");
                        } else {
                            $info->load('region');
                            $anchor->setAttribute('href', config('base.dotlanUrl'). "map/". $info->region->nameDotlanFormat() . "/{$info->name}");
                        }
                        continue;
                    }

                    if ($targetType == 5) {
                        $info = $this->getSystem($targetId);
                        $status = $info->status;
                        $payload = $info->payload;
                        if (!$status) {
                            if ($payload->code >= 400 && $payload->code < 500) {
                                activity(__METHOD__)->withProperties($payload)->log($payload->message);
                            }
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.kmUrl'). "system/{$targetId}");
                        } else {
                            $payload->load('constellation.region');
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.dotlanUrl'). "map/". $payload->constellation->region->nameDotlanFormat()."/".$payload->name);
                        }
                        continue;
                    }

                    $targetTypeData = $this->getType($targetType);
                    $status = $targetTypeData->status;
                    $payload = $targetTypeData->payload;
                    if (!$status) {
                        continue;
                    }

                    $payload->load('group');
                    $targetGroup = $payload->group;
                    if ($targetGroup->id == 1) {
                        $anchor->removeAttribute('href');
                        $anchor->setAttribute('href', config('base.kmUrl'). "character/{$targetId}/");
                        continue;
                    }

                    if ($targetGroup->id == 2) {
                        $anchor->removeAttribute('href');
                        $anchor->setAttribute('href', config('base.kmUrl'). "corporation/{$targetId}/");
                        continue;
                    }

                    if ($targetGroup->id == 15) {
                        $station = $this->getStation($targetId);
                        $status = $station->status;
                        $payload = $station->payload;
                        if (!$status) {
                            if ($payload->code >= 400 && $payload->code < 500) {
                                activity(__METHOD__)->withProperties($payload)->log($payload->message);
                            }
                            continue;
                        }

                        if (in_array($targetType, [21646,21644,21642,21645])) {
                            $payload->load('system');
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.dotlanUrl'). "outpost/". $payload->system->name);
                        } else {
                            $anchor->removeAttribute('href');
                            $anchor->setAttribute('href', config('base.dotlanUrl'). "station/". $payload->nameDotlanFormat());
                        }
                        continue;
                    }

                    if ($targetGroup->id == 19) {
                        $anchor->removeAttribute('href');
                        $anchor->setAttribute('href', config('base.kmUrl'). "faction/{$targetId}/");
                        continue;
                    }

                    if ($targetGroup->id == 32) {
                        $anchor->removeAttribute('href');
                        $anchor->setAttribute('href', config('base.kmUrl'). "alliance/{$targetId}/");
                        continue;
                    }
                    if ($targetGroup->category_id == 65) {
                        $anchor->removeAttribute('href');
                        $anchor->setAttribute('href', config('base.kmUrl'). "ship/{$targetType}/");
                        continue;
                    }
                }

                if ($type === "killReport") {
                    $killId = $href[1];
                    $anchor->removeAttribute('href');
                    $anchor->setAttribute('href', config('base.kmUrl')."kill/{$killId}/");
                    continue;
                }

                if ($type === "fitting") {
                    unset($href[0]);
                    $dna = implode(':', $href);
                    $anchor->removeAttribute('href');
                    $anchor->setAttribute('href', config('base.osmiumUrl') . "loadout/dna/{$dna}");
                    continue;
                }
            }
            $header->fill([
                'body' => $dom->saveHTML()
            ]);
            $header->save();
        }

        return (object)[
            'status' => true,
            'payload' => $header
        ];

    }

    public function getMemberMailLabels ($member)
    {
        $request = $this->httpCont->getCharactersCharacterIdMailLabels($member->id, $member->access_token);
        $status = $request->status;
        $payload = $request->payload;
        if (!$status) {
            return $request;
        }
        $response = collect($payload->response)->recursive();
        $member->mail_labels()->updateOrCreate([], [
            'total_unread_count' => $response->get('total_unread_count'),
            'labels' => $response->get('labels')->toJson()
        ]);
        return $request;
    }

    public function getMemberMailHeaders($member, $pages=1)
    {
        $headers = collect();
        $last_mail_id=null;
        for($x=1;$x<=$pages;$x++) {
            $request = $this->httpCont->getCharactersCharacterIdMail($member->id, $member->access_token, $last_mail_id);
            if (!$request->status) {
                return $request;
            }
            $response = collect($request->payload->response)->recursive()->keyBy('mail_id');
            if ($response->count() != 50) {
                break;
            }
            $headers = $headers->merge($response);
            $last_mail_id = $headers->last()->get('mail_id');
            usleep(250000);
        }
        $now = now();
        $recipients = collect();
        $headers->chunk(25)->each(function($chunk) use(&$now, $member, &$recipients) {
            $mail_ids = $chunk->pluck('mail_id');
            $knownMails = MailHeader::whereIn('id', $mail_ids->toArray())->with('members')->get()->keyBy('id');
            $attach = collect();
            $chunk->each(function ($header) use ($knownMails, &$now, $member, $attach, &$recipients) {
                if (!$knownMails->has($header->get('mail_id'))) {
                    // Break Down the Header. Parse Sender and Recipients, dispatch Jobs to get Body
                    $mailHeader = new MailHeader(['id' => $header->get('mail_id')]);
                    $isCharacter = $this->getCharacter($header->get('from'));
                    if ($isCharacter->status) {
                        $mailHeader->fill(['sender_id' => $header->get('from'), 'sender_type' => "character"]);
                    } else {
                        $isMailingList = MailingList::firstOrNew(['id' => $header->get('from')]);
                        if (!$isMailingList->exists) {
                            $isMailingList->fill([
                                'name' => "Unknown Mailing List ". $header->get('from')
                            ]);
                            $isMailingList->save();
                        }
                        $mailHeader->fill(["sender_id" => $header->get('from'), "sender_type" => "mailing_list", "is_on_mailing_list" => 1, "mailing_list_id" => $header->get('from')]);
                    }
                    $mailHeader->fill([
                        'subject' => $header->get('subject'),
                        'sent' => Carbon::parse($header->get('timestamp'))->toDateTimeString()
                    ]);
                    $mailHeader->save();
                    $mailHeader->recipients()->createMany($header->get('recipients')->toArray());

                    ProcessMailHeader::dispatch($member, $mailHeader, $header->get('recipients'))->delay($now);
                    $now = $now->addSeconds(1);
                }
                $attach->put($header->get('mail_id'), [
                    'labels' => $header->get('labels')->implode(','),
                    'is_read' => $header->get('is_read'),
                ]);
            });

            $member->mail()->syncWithoutDetaching($attach->toArray());
        });

        return (object)[
            'status' => true,
            'payload' => $headers
        ];
    }

    public function getMemberMailingLists($member)
    {
        $request = $this->httpCont->getCharactersCharacterIdMailLists($member->id, $member->access_token);
        if (!$request->status) {
            return $request;
        }
        $response = collect($request->payload->response)->keyBy('mailing_list_id');

        $mailListIds = $response->keys();
        $knownMLs = MailingList::whereIn('id', $mailListIds)->get()->keyBy('id');
        foreach ($mailListIds as $key => $mailListId) {
            if ($knownMLs->has($mailListId)) {
                $mailingList = $knownMLs->get($mailListId);
                if ($mailingList->name === "Unknown Mailing List ". $mailingList->id) {
                    $mailingList->update([
                        'name' => $response->get($mailListId)->name,
                    ]);
                }
                $mailListIds->forget($key);
            } else {
                MailingList::create([
                    'id' => $mailListId,
                    'name' => $response->get($mailListId)->name
                ]);

            }
        }
        $member->mailing_lists()->attach($mailListIds->toArray());
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
        $skills->diffKeys($knownTypes)->each(function ($skill) use (&$now, &$x) {
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

    public function getMemberWalletTransactions(Member $member)
    {
        $wTransactionRequest = $this->httpCont->getCharactersCharacterIdWalletTransactions($member->id, $member->access_token);
        $status = $wTransactionRequest->status;
        $wTransactionPayload = $wTransactionRequest->payload;
        if (!$status) {
            return $wTransactionRequest;
        }
        $wTransactionResponse = collect($wTransactionPayload->response)->recursive()->keyBy('transaction_id');

        $transactionIds = $wTransactionResponse->keys();

        $knownTransactions = $member->transactions()->whereIn('transaction_id', $transactionIds->toArray())->get()->keyBy('transaction_id');
        $transactions = $transactionIds->diff($knownTransactions->keys());
        $transactions = $wTransactionResponse->whereIn('transaction_id', $transactions);


        $clients = $transactions->pluck('client_id');
        $types = $transactions->pluck('type_id');

        $ids = $clients->merge($types);
        $pUNRequest = $this->postUniverseNames($ids);
        $pUNStatus = $pUNRequest->status;
        $pUNPayload = $pUNRequest->payload;
        if (!$pUNStatus) {
            return $pUNRequest;
        }
        $pUNResponse = collect($pUNPayload->response)->recursive()->keyBy('id');
        $characterIds = $pUNResponse->where('category', 'character')->pluck('id')->unique()->values();
        $knownCharacters = Character::whereIn('id', $characterIds->toArray())->get()->keyBy('id');
        $now = now(); $x = 0;
        $characterIds->diff($knownCharacters->keys())->each(function ($characterId) use (&$now, &$x) {
            GetCharacter::dispatch($characterId)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $corporationIds = $pUNResponse->where('category', 'corporation')->pluck('id')->unique()->values();
        $knownCorporations = Corporation::whereIn('id', $corporationIds->toArray())->get()->keyBy('id');
        $corporationIds->diff($knownCorporations->keys())->each(function ($corporationId) use (&$now, &$x) {
            GetCorporation::dispatch($corporationId)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $typeIds = $pUNResponse->where('category', 'inventory_type')->pluck('id')->unique()->values();
        $knownTypes = Type::whereIn('id', $typeIds->toArray())->get()->keyBy('id');
        $typeIds->diff($knownTypes->keys())->each(function ($typeId) use (&$now, &$x) {
            GetType::dispatch($typeId)->delay($now);
            if ($x%10==0) {
                $now->addSecond();
            }
            $x++;
        });

        $scopes = $member->scopes;
        $locations = $transactions->pluck('location_id')->unique()->values()->each(function ($location) use ($scopes) {
            if ($location > 1000000000000) {
                $structure = Structure::find($location);
                if (!is_null($structure)) {
                    if ($structure->name === "Unknown Structure ". $location) {
                        GetStructure::dispatch($member, $location)->delay($now);
                        if ($x%10==0) {
                            $now->addSecond();
                        }
                        $x++;
                    }
                } else {
                    GetStructure::dispatch($member, $location)->delay($now);
                    if ($x%10==0) {
                        $now->addSecond();
                    }
                    $x++;
                }

            } else {
                $station = Station::find($location);
                if (is_null($station)) {
                    GetStation::dispatch($location)->delay($now);
                    if ($x%10==0) {
                        $now->addSecond();
                    }
                    $x++;
                }
            }
        });
        $transactionIds = $transactions->keys();
        $unknownTransactions = collect();
        $transactionIds->diff($knownTransactions->keys())->each(function ($transactionId) use ($transactions, $unknownTransactions, $pUNResponse) {
            $transaction = $transactions->get($transactionId);
            $unknownTransactions->push([
                'transaction_id' => $transaction->get('transaction_id'),
                'client_id' => $transaction->get('client_id'),
                'client_type' => $pUNResponse->has($transaction->get('client_id')) ? $pUNResponse->get($transaction->get('client_id'))->get('category') : null,
                'is_buy' => $transaction->get('is_buy'),
                'is_personal' => $transaction->get('is_personal'),
                'journal_ref_id' => $transaction->get('journal_ref_id'),
                'location_id' => $transaction->get('location_id'),
                'location_type' => $transaction->get('location_id') > 1000000000000 ? "structure" : "station",
                'quantity' => $transaction->get('quantity'),
                'type_id' => $transaction->get('type_id'),
                'unit_price' => $transaction->get('unit_price'),
                'date' => Carbon::parse($transaction->get('date')),
            ]);
        });
        $member->transactions()->createMany($unknownTransactions->toArray());
        return $wTransactionRequest;
    }

    public function getMemberWalletJournals (Member $member)
    {
        $request = $this->httpCont->getCharactersCharacterIdWalletJournal($member->id, $member->access_token);
        $status = $request->status;
        $payload = $request->payload;
        if (!$status) {
            return $request;
        }

        $response = collect($payload->response)->recursive()->keyBy('id');
        $knownJournals = $member->journals()->whereIn('journal_id', $response->keys())->get()->keyBy('journal_id');

        $esiJournals = $response->diffKeys($knownJournals);
        if ($esiJournals->isEmpty()) {
            return $request;
        }

        $firstPartyIds = $esiJournals->pluck('first_party_id');
        $secondPartyIds = $esiJournals->pluck('second_party_id');
        $partyIds = $firstPartyIds->merge($secondPartyIds)->unique()->reject(function($id) {
            return is_null($id);
        })->values()->push(500001);


        $factionPartyIds = $partyIds->filter(function ($id, $key) use ($partyIds) {
            if ($id >= 500000 && $id < 1000000) {
                $partyIds->forget($key);
                return true;
            }
        });

        $postUniverseNamesRequest = $this->postUniverseNames($partyIds);
        $postUniverseNamesStatus = $postUniverseNamesRequest->status;
        $postUniverseNamesPayload = $postUniverseNamesRequest->payload;
        if (!$status) {
            return $postUniverseNamesRequest;
        }
        $postUniverseNamesResponse = collect($postUniverseNamesPayload->response)->recursive()->keyBy('id');

        $journals = collect();
        $esiJournals->each(function ($journal) use ($journals, $postUniverseNamesResponse)  {
            $x = collect([
                'journal_id' => $journal->get('id'),
                'ref_type' => $journal->get('ref_type'),
                'context_id' => $journal->get('context_id'),
                'context_type' => $journal->get('context_id_type'),
                'description' => $journal->get('description'),
                'date' => Carbon::parse($journal->get('date')),
                'reason' => $journal->get('reason'),
                'first_party_id' => $journal->get('first_party_id'),
                'second_party_id' => $journal->get('second_party_id'),
                'amount' => $journal->get('amount'),
                'balance' => $journal->get('balance'),
                'tax' => $journal->get('tax'),
                'tax_receiver_id' => $journal->get('tax_receiver_id')
            ]);
            if (!is_null($x->get('first_party_id'))) {
                $id = $x->get('first_party_id');
                if ($id >= 500000 && $id < 1000000) {
                    $x->put('first_party_type', 'faction');
                } else if ($postUniverseNamesResponse->has($id))  {
                    $x->put('first_party_type', $postUniverseNamesResponse->get($id)->get('category'));
                }
            }
            if (!is_null($x->get('second_party_id'))) {
                $id = $x->get('second_party_id');
                if ($id >= 500000 && $id < 1000000) {
                    $x->put('second_party_type', 'faction');
                } else if ($postUniverseNamesResponse->has($id))  {
                    $x->put('second_party_type', $postUniverseNamesResponse->get($id)->get('category'));
                }
            }
            $journals->push($x);
        });
        $member->journals()->createMany($journals->toArray());
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
                    if (!$dbAttributes->has($attribute->get('attribute_id')) && is_int($attribute->get('value'))) {
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

    public function getGroup($id)
    {
        return $this->httpCont->getUniverseGroupsGroupId($id);
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

    public function getChrFactions()
    {
        return $this->httpCont->getChrFactions();
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
