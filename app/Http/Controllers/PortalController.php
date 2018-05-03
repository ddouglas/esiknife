<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Input, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Models\SDE\Group;
use ESIK\Models\ESI\{Station, System,Type};
use ESIK\Http\Controllers\DataController;

class PortalController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
    }
    public function dashboard ()
    {
        return view('portal.dashboard');
    }

    public function access()
    {
        if (Request::isMethod('post')) {
            if (Request::has('scope')) {
                $scope = Request::get('scope');
                if ($scope === "accessor") {
                    if (Request::has('action')) {
                        $action = Request::get('action');
                        if ($action === "search") {
                            $validator = Validator::make(Request::all(), [
                                'char_name' => "required|min:3|max:50"
                            ]);
                            if ($validator->fails()) {
                                return redirect(route('access'))->withErrors($validator)->withInput();
                            }
                            $search = $this->search(Request::get('char_name'));
                            $status = $search->status;
                            $payload = $search->payload;
                            if (!$status) {
                                Session::flash('alert', [
                                    'header' => "No Results Returned",
                                    'message' => "We received 0 results for the term ". Request::get('char_name') . ". Please try again with a different or more refined  name",
                                    'type' => 'danger',
                                    'close' => 1
                                ]);
                                return redirect(route('access'));
                            }
                            return view('portal.access', [
                                'results' => collect($payload->response)->recursive()
                            ]);
                        }
                        if ($action === "modify") {
                            $validator = Validator::make(Request::all(), [
                                'access' => "required|array|min:1"
                            ],[
                                'access.required' => "Each Accessor must have atleast one scope assigned to them. Delete the accessors access with the red trashcan if no access is desired",
                                'access.array' => "Invalid Format. DO NOT MODIFY FORM",
                                'access.min' => "Each Accessor must have atleast one scope assigned to them. Delete the accessors access with the red trashcan if no access is desired"
                            ]);
                            if ($validator->fails()) {
                                return redirect(route('access'))->withErrors($validator)->withInput();
                            }
                            $access = collect(Request::get('access'))->recursive();
                            $access->each(function ($scopes, $charId) use ($access) {
                                $scopes->keys()->each(function ($scope) use ($charId, $access) {
                                    $access->get($charId)->push($scope);
                                    $access->get($charId)->forget($scope);
                                });
                            });
                            $access->each(function ($scopes, $accessorId) {
                                Auth::user()->accessor()->updateExistingPivot($accessorId, [
                                    'access' => $scopes->toJson()
                                ]);
                            });
                            return redirect(route('access'));
                        }
                    }
                    if (Request::has('select')) {
                        $selection = (int) Request::get('select');
                        if (Auth::user()->id == $selection) {
                            Session::flash('alert', [
                                'header' => "You bein serious?",
                                'message' => "You cannot grant yourself access to your own data. Please don't do this again.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('access'));
                        }
                        $selection = $this->dataCont->getCharacter($selection);
                        $isMember = Member::find($selection->payload->id);
                        if (is_null($isMember)) {
                            Session::flash('alert', [
                                'header' => "Not A Member",
                                'message' => "That character who you are attempting to grant access to is not a member of ESIKnife. Please have them register for ESI Knife, and then you will be able to share your data with them.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('access'));
                        }
                        Auth::user()->accessor()->attach(collect([
                            $isMember->id => [
                                'expires' => now()->addDay(),
                                'access' => Auth::user()->scopes
                            ]
                        ]));
                        Session::flash('alert', [
                            'header' => "Access Granted",
                            'message' => "You have successfully granted access to ". $isMember->info->name .".",
                            'type' => 'success',
                            'close' => 1
                        ]);
                        return redirect(route('access'));
                    }

                    if (Request::has('remove')) {
                        $selection = (int) Request::get('remove');
                        if (Auth::user()->id == $selection) {
                            Session::flash('alert', [
                                'header' => "You bein serious?",
                                'message' => "You cannot revoke access to your own data. Please don't do this again.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('access'));
                        }
                        $isMember = Member::find($selection);
                        if (is_null($isMember)) {
                            Session::flash('alert', [
                                'header' => "Not A Member",
                                'message' => "That character who you are attempting to revoke access for is not a member of ESIKnife.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('access'));
                        }
                        Auth::user()->accessor()->detach($selection);
                        Session::flash('alert', [
                            'header' => "Access Revoked",
                            'message' => "You have successfully revoke ". $isMember->info->name ." any access to your data via ESIKnife.",
                            'type' => 'success',
                            'close' => 1
                        ]);
                        return redirect(route('access'));
                    }
                }
            }
        }

        Auth::user()->load('accessor', 'accessee');
        return view('portal.access');
    }

    public function assets()
    {
        $dbAssets = Auth::user()->assets->keyBy('item_id')->toArray();
        $dbAssets = collect($dbAssets)->recursive();
        $assets = collect();
        $locations = collect();
        $dbAssets->each(function ($dbAsset) use (&$dbAssets,&$assets, $locations) {
            if ($dbAssets->has($dbAsset->get('location_id'))) {

                $locationAsset = $dbAssets->get($dbAsset->get('location_id'));
                if (!$locationAsset->has('contents')) {
                    $locationAsset->put('contents', collect());
                }
                $locationAsset->get('contents')->put($dbAsset->get('item_id'), $dbAsset);
            }
            if (!$dbAssets->has($dbAsset->get('location_id'))) {
                $locations->push($dbAsset->get('location_id'));
            }
        });
        $locations = $locations->unique()->values();
        $systems = $locations->filter(function ($location) {
            return $location >= 30000000 && $location < 32000000;
        });
        $stations = $locations->filter(function ($location) {
            return $location >= 60000000 && $location < 64000000;
        });
        $structures = $locations->filter(function ($location) {
            return $location >= 1000000000000 && $location < 2000000000000;
        });
        // $structures->each(function ($structureId) use ($dbAssets)  {
        //     dump($dbAssets->where('location_id', $structureId));
        // });

        // $structures->each(function ($id) {
        //     dump($this->dataCont->getStructure(Auth::user(), $id));
        //     usleep(50000);
        // });

        System::whereIn('id', $systems->toArray())->get()->each(function ($systemInfo) use ($assets, $dbAssets) {
            $assets->put($systemInfo->id, collect([
                'info' => $systemInfo,
                'assets' => $dbAssets->where('location_id', $systemInfo->id)
            ]));
        });

        Station::whereIn('id', $stations->toArray())->get()->each(function ($stationInfo) use ($assets, $dbAssets) {
            $assets->put($stationInfo->id, collect([
                'info' => $stationInfo,
                'assets' => $dbAssets->where('location_id', $stationInfo->id)
            ]));
        });

        $structures->each(function ($structureId) use ($assets, $dbAssets) {
            $structureInfo = $this->dataCont->getStructure(Auth::user(), $structureId);
            $assets->put($structureId, collect([
                'info' => $structureInfo->payload,
                'assets' => $dbAssets->where('location_id',$structureId)
            ]));
        });

        dd($assets);
    }

    public function bookmarks () {
        Auth::user()->load('bookmarks', 'bookmarkFolders');
        return view('portal.bookmarks');
    }

    public function clones ()
    {
        if (Auth::user()->scopes->contains(config('services.eve.scopes.readCharacterImplants'))) {
            Auth::user()->load('implants.attributes');
        }
        if (Auth::user()->scopes->contains(config('services.eve.scopes.readCharacterClones'))) {
            Auth::user()->load('clone', 'jumpClones');
        }
        return view('portal.clones');
    }

    public function contacts ()
    {
        Auth::user()->load('contacts.info', 'contact_labels');
        return view('portal.contacts');
    }

    public function contracts ()
    {
        Auth::user()->load('contracts', 'contracts.issuer.corporation', 'contracts.acceptor', 'contracts.assignee', 'contracts.start', 'contracts.end');
        $contracts = Auth::user()->contracts()->paginate(25);
        return view('portal.contracts.list', [
            'contracts' => $contracts
        ]);
    }

    public function contract ($id)
    {
        $contract = Auth::user()->contracts()->where('id', $id)->with('issuer.corporation', 'acceptor', 'assignee', 'start', 'end', 'items')->first();
        return view('portal.contracts.view', [
            'contract' => $contract
        ]);
    }

    public function flyable ()
    {
        $skillz = Auth::user()->skillz->keyBy('id');
        $shipGroups = Group::where('category_id', 6)->get()->pluck('id');
        $ships = Type::whereIn('group_id', $shipGroups)->where('published', 1)->with('skillAttributes', 'group')->get()->keyBy('id');
        $skillzAttributeMap = collect(config('services.eve.dogma.attributes.skillz.map'));
        $flyable = collect();
        $ships->each(function ($ship) use ($flyable, $skillz, $skillzAttributeMap) {
            $ship->canFly = false;
            $shipAttributes = $ship->skillAttributes->keyBy('attribute_id');

            $skillzAttributeMap->each(function ($skillLevel, $skillId) use ($skillz, $shipAttributes, $ship) {
                if ($shipAttributes->has($skillId) && $shipAttributes->has($skillLevel)) {
                    $requiredSkill = (int)$shipAttributes->get($skillId)->value;
                    $requiredSkillLevel = (int)$shipAttributes->get($skillLevel)->value;
                    if ($skillz->has($requiredSkill) && $skillz->get($requiredSkill)->pivot->active_skill_level >= (int)$requiredSkillLevel) {
                        $ship->canFly = true;
                    } else {
                        $ship->canFly = false;
                        return false;
                    }
                }
            });
            if (!$flyable->has($ship->group_id)) {
                $flyable->put($ship->group_id, collect([
                    'name' => $ship->group->name,
                    'key' => strtolower(implode('_', explode(' ', $ship->group->name))),
                    'ships' => collect([
                        'can' => collect(),
                        'cant' => collect()
                    ])
                ]));
            }
            if ($ship->canFly) {
                $flyable->get($ship->group_id)->get('ships')->get('can')->put($ship->id, $ship);
            } else {
                $flyable->get($ship->group_id)->get('ships')->get('cant')->put($ship->id, $ship);
            }
        });
        return view('portal.skillz.flyable', [
            'flyable' => $flyable
        ]);
    }

    public function mails()
    {
        $mails = Auth::user()->mail();
        if (Request::has('label')) {
            $label = Request::get('label');
            $mails = $mails->whereRaw("FIND_IN_SET('" . $label . "', member_mail_headers.labels)");
        } elseif (Request::has('ml')) {
            $mailing_list_id = Request::get('ml');
            $mails->where('mailing_list_id', $mailing_list_id);
        }
        if (Request::has('unread')) {
            $mails = $mails->where('member_mail_headers.is_read', NULL);
        }
        $mails = $mails->orderby('sent', 'desc')->with('sender')->paginate(50);
        return view('portal.mail.list', [
           'mails' => $mails
       ]);
    }

    public function mail($id)
    {
        $mail = Auth::user()->mail()->where('id', $id)->with('recipients', 'sender')->first();
        if (is_null($mail)) {
            Session::flash('alert', [
                "header" => "Invalid Mail ID",
                'message' => "That mail id either does not exist or is not associated with this character.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route('mail.welcome'));
        }
        return view ('portal.mail.view', [
            'mail' => $mail
        ]);
    }

    public function queue ()
    {
        $groupsTraining = collect();
        $spTraining = collect();

        Auth::user()->skillQueue->load('group')->each(function ($item) use ($spTraining, $groupsTraining) {
            if (!$groupsTraining->has($item->group_id)) {
                $item->training = 0;
                $groupsTraining->put($item->group_id, $item);
            }
            $groupsTraining->get($item->group_id)->training = $groupsTraining->get($item->group_id)->training + 1;
            if (!is_null($item->pivot->level_end_sp) && !is_null($item->pivot->training_start_sp)) {
                $spTraining->push($item->pivot->level_end_sp - $item->pivot->training_start_sp);
            }
        });
        $queueComplete = "No Skills are currently training";
        if (Auth::user()->skillQueue->isNotEmpty()) {
            $lastSkill = Auth::user()->skillQueue->last();

            if (!is_null($lastSkill->pivot->finish_date)) {
                $queueComplete = Carbon::parse($lastSkill->pivot->finish_date)->toDayDateTimeString();
            }
        }
        return view('portal.skillz.queue', [
            'groupsTraining' => $groupsTraining,
            'spTraining' => $spTraining,
            'queueComplete' => $queueComplete
        ]);
    }



    public function skills ()
    {
        $skillz = collect();
        Auth::user()->skillz->load('group')->each(function ($skill) use ($skillz) {
            if (!$skillz->has($skill->group_id)) {
                $skillz->put($skill->group_id, collect([
                    'name' => $skill->group->name,
                    'skills' => collect()
                ]));
            }
            $skillz->get($skill->group_id)->get('skills')->put($skill->skill_id, $skill);
        });
        return view('portal.skillz.list', [
            'skillz' => $skillz
        ]);
    }

    public function wallet_transactions ()
    {
        $transactions = Auth::user()->transactions()->paginate(25);
        return view('portal.wallet.transactions', [
            'transactions' => $transactions
        ]);
    }

    public function wallet_journal ()
    {
        $journal = Auth::user()->journals()->orderby('date', 'desc')->paginate(25);
        return view('portal.wallet.journals', [
            'journal' => $journal
        ]);
    }

    public function welcome ()
    {
        if (Request::isMethod('post')) {
            $validator = Validator::make(Request::all(), [
                'scopes' => "array|required|min:1"
            ]);
            if ($validator->failed()) {
                return redirect(route('welcome'))->withErrors($validator);
            }
            $selected = collect(Request::get('scopes'))->keys();
            $authorized = $selected->map(function($scope) {
                return config('services.eve.scopes')[$scope];
            });

            $authorized = $authorized->sort()->values()->implode(' ');

            $state_hash = str_random(16);
            $state = collect([
                "redirectTo" => "welcome",
                "additionalData" => collect([
                    'authorizedScopesHash' => hash('sha1', $authorized)
                ])
            ]);
            Session::put($state_hash, $state);
            $ssoUrl = config("services.eve.urls.sso")."/oauth/authorize?response_type=code&redirect_uri=" . route(config('services.eve.sso.callback')) . "&client_id=".config('services.eve.sso.id')."&state={$state_hash}&scope=".$authorized;
            return redirect($ssoUrl);
        }
        if (Auth::check()) {
            return redirect(route('dashboard'));
        }
        if (Request::has('state')) {
            $ssoResponse = Session::get(Request::get('state'));
            // Session::forget(Request::get('state'));
            $hashedResponseScopes = hash('sha1', collect(explode(' ', $ssoResponse->get('Scopes')))->sort()->values()->implode(' '));
            if ($hashedResponseScopes !== $ssoResponse->get('authorizedScopesHash')) {
                Session::flash('alert', [
                    "header" => "Unable to Verify Requested Scopes",
                    'message' => "We are unable to verify that the scopes requested were the scopes that were authorized. Please use the link below to attempt the authentication again. If this error persists, contact IT.",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('welcome'));
            }
            $getMemberData = $this->dataCont->getMemberData($ssoResponse->get('CharacterID'));

            $status = $getMemberData->status;
            $payload = $getMemberData->payload;
            if (!$status) {
                Session::flash('alert', [
                    "header" => "There was an issue authorizing the scopes you selected",
                    'message' => "We had an issue authorizing the scopes that you authorized. If the issue persists, please report the error via bitbucket using error id <strong>" . $payload->log_id . "</strong>",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('welcome'));
            }

            $memberData = $getMemberData->payload;
            $member = Member::firstOrNew(['id' => $memberData->id]);
            if (!$member->exists) {
                $member->fill([
                    'raw_hash' => $ssoResponse->get('CharacterOwnerHash'),
                    'hash' => hash('sha256', $ssoResponse->get('CharacterOwnerHash')),
                    'scopes' => json_encode(explode(' ', $ssoResponse->get('Scopes'))),
                    'access_token' => $ssoResponse->get('access_token'),
                    'refresh_token' => $ssoResponse->get('refresh_token'),
                    'expires' => Carbon::now()->addSeconds($ssoResponse->get('expires_in'))->toDateTimeString()
                ]);
            } else if (hash('sha256', $ssoResponse->get('CharacterOwnerHash')) !== $member->hash) {
                $member->delete();
                $member = Member::create([
                    'id' =>  $memberData->id,
                    'raw_hash' => $ssoResponse->get('CharacterOwnerHash'),
                    'hash' => hash('sha256', $ssoResponse->get('CharacterOwnerHash')),
                    'scopes' => json_encode(explode(' ', $ssoResponse->get('Scopes'))),
                    'access_token' => $ssoResponse->get('access_token'),
                    'refresh_token' => $ssoResponse->get('refresh_token'),
                    'expires' => Carbon::now()->addSeconds($ssoResponse->get('expires_in'))->toDateTimeString()
                ]);
            } else {
                $member->fill([
                    'scopes' => json_encode(explode(' ', $ssoResponse->get('Scopes'))),
                    'access_token' => $ssoResponse->get('access_token'),
                    'refresh_token' => $ssoResponse->get('refresh_token'),
                    'disabled' => 0,
                    'disabled_reason' => null,
                    'disabled_timestamp' => null,
                    'expires' => Carbon::now()->addSeconds($ssoResponse->get('expires_in'))->toDateTimeString()
                ]);
            }

            $member->save();
            $alert = collect();
            $scopes = collect(json_decode($member->scopes, true));

            if ($scopes->contains(config('services.eve.scopes.readCharacterAssets'))) {
                $getMemberAssets = $this->dataCont->getMemberAssets ($member);
                $status = $getMemberAssets->status;
                $payload = $getMemberAssets->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your bookmarks right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterBookmarks'))) {
                $getMemberBookmarks = $this->dataCont->getMemberBookmarks($member);
                $status = $getMemberBookmarks->status;
                $payload = $getMemberBookmarks->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your bookmarks right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterClones'))) {
                $getMemberClones = $this->dataCont->getMemberClones($member, $scopes);
                $status = $getMemberClones->status;
                $payload = $getMemberClones->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your clones right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterContacts'))) {
                $getMemberContacts = $this->dataCont->getMemberContacts($member);
                $status = $getMemberContacts->status;
                $payload = $getMemberContacts->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your contacts right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterContracts'))) {
                $getMemberContracts = $this->dataCont->getMemberContracts($member, $scopes);
                $status = $getMemberContracts->status;
                $payload = $getMemberContracts->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your contracts right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterImplants'))) {
                $getMemberImplants = $this->dataCont->getMemberImplants($member, $scopes);
                $status = $getMemberImplants->status;
                $payload = $getMemberImplants->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your implants right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterLocation'))) {
                $getMemberLocation = $this->dataCont->getMemberLocation($member, $scopes);
                $status = $getMemberLocation->status;
                $payload = $getMemberLocation->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your location right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterMails'))) {
                $getMemberMailLabels = $this->dataCont->getMemberMailLabels($member);
                $status = $getMemberMailLabels->status;
                $payload = $getMemberMailLabels->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your mail labels right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }

                $getMemberMailingLists = $this->dataCont->getMemberMailingLists($member);
                $status = $getMemberMailingLists->status;
                $payload = $getMemberMailingLists->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your mailing lists right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }

                $getMemberMailHeaders = $this->dataCont->getMemberMailHeaders($member);
                $status = $getMemberMailHeaders->status;
                $payload = $getMemberMailHeaders->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your mail headers right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }
            if ($scopes->contains(config('services.eve.scopes.readCharacterShip'))) {
                $getMemberShip = $this->dataCont->getMemberShip($member);
                $status = $getMemberShip->status;
                $payload = $getMemberShip->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your ship right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterSkills'))) {
                $getMemberSkillz = $this->dataCont->getMemberSkillz($member);
                $status = $getMemberSkillz->status;
                $payload = $getMemberSkillz->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your skills right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterSkillQueue'))) {
                $getMemberSkillQueue = $this->dataCont->getMemberSkillqueue($member);
                $status = $getMemberSkillQueue->status;
                $payload = $getMemberSkillQueue->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your skill queue right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains(config('services.eve.scopes.readCharacterWallet'))) {
                $getMemberWallet = $this->dataCont->getMemberWallet($member);
                $status = $getMemberWallet->status;
                $payload = $getMemberWallet->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }

                unset($status, $payload);
                $getMemberWalletTransaction = $this->dataCont->getMemberWalletTransactions($member);
                $status = $getMemberWalletTransaction->status;
                $payload = $getMemberWalletTransaction->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet transaction right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }

                unset($status, $payload);
                $getMemberWalletJournals = $this->dataCont->getMemberWalletJournals($member);
                $status = $getMemberWalletJournals->status;
                $payload = $getMemberWalletJournals->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet transaction right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }
            if (!Auth::check()) {
                Auth::login($member);
            }
            return redirect(route('dashboard'));
        }
        return view('portal.welcome');
    }

    public function search($term) {
        $getSearch = $this->dataCont->getSearch("character", $term);
        $status = $getSearch->status;
        $payload = $getSearch->payload;

        if (!$status) {
            return $getSearch;
        }

        $results = collect($payload->response)->recursive();
        if ($results->has('character')) {
            $names = $this->dataCont->postUniverseNames($results->get('character'));
            return $names;
        } else {
            return (object)[
                'status' => false,
                'payload' => collect()
            ];
        }
    }
}
