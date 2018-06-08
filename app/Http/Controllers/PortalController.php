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
        Auth::user()->load('accessee.info');
        return view('portal.dashboard');
    }

    public function overview ($member)
    {
        $member = Member::findOrFail($member);
        return view('portal.overview', [
            'member' => $member
        ]);
    }

    public function assets ($member)
    {
        $member = Member::findOrFail($member);
        $dbAssets = $member->assets->keyBy('item_id')->toArray();
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

        $structures->each(function ($structureId) use ($assets, $dbAssets, $member) {
            $structureInfo = $this->dataCont->getStructure($member, $structureId);
            $assets->put($structureId, collect([
                'info' => $structureInfo->payload,
                'assets' => $dbAssets->where('location_id', $structureId)
            ]));
        });

        $assets = $assets->sortBy('info.name');

        return view('portal.assets', [
            'assets' => $assets
        ])->withMember($member);
    }

    public function bookmarks ($member) {
        $member = Member::findOrFail($member);
        $member->load('bookmarks', 'bookmarkFolders');
        return view('portal.bookmarks')->withMember($member);
    }

    public function clones ($member)
    {
        $member = Member::findOrFail($member);
        if ($member->scopes->contains(config('services.eve.scopes.readCharacterImplants'))) {
            $member->load('implants.attributes');
        }
        if ($member->scopes->contains(config('services.eve.scopes.readCharacterClones'))) {
            $member->load('clone', 'jumpClones');
        }
        return view('portal.clones')->withMember($member);
    }

    public function contacts ($member)
    {
        $member = Member::findOrFail($member);
        $member->load('contacts.info', 'contact_labels');
        return view('portal.contacts')->withMember($member);
    }

    public function contracts ($member)
    {
        $member = Member::findOrFail($member);
        $member->load('contracts', 'contracts.issuer.corporation', 'contracts.acceptor', 'contracts.assignee', 'contracts.start', 'contracts.end');
        $contracts = $member->contracts()->paginate(25);
        return view('portal.contracts.list', [
            'contracts' => $contracts
        ])->withMember($member);
    }

    public function contract ($member, $contract_id)
    {
        $member = Member::findOrFail($member);
        $contract = $member->contracts()->where('id', $contract_id)->with('issuer.corporation', 'acceptor', 'assignee', 'start', 'end', 'items')->first();
        return view('portal.contracts.view', [
            'contract' => $contract
        ])->withMember($member);
    }

    public function flyable ($member)
    {
        $member = Member::findOrFail($member);
        $skillz = $member->skillz->keyBy('id');
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
        ])->withMember($member);
    }

    public function mails ($member)
    {
        $member = Member::findOrFail($member);
        $mails = $member->mail();
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
       ])->withMember($member);
    }

    public function mail ($member, $id)
    {
        $member = Member::findOrFail($member);
        $mail = $member->mail()->where('id', $id)->with('recipients', 'sender')->first();
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
        ])->withMember($member);
    }

    public function queue ($member)
    {
        $member = Member::findOrFail($member);
        $groupsTraining = collect();
        $spTraining = collect();

        $member->skillQueue->load('group')->each(function ($item) use ($spTraining, $groupsTraining) {
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
        if ($member->skillQueue->isNotEmpty()) {
            $lastSkill = $member->skillQueue->last();

            if (!is_null($lastSkill->pivot->finish_date)) {
                $queueComplete = Carbon::parse($lastSkill->pivot->finish_date)->toDayDateTimeString();
            }
        }
        return view('portal.skillz.queue', [
            'groupsTraining' => $groupsTraining,
            'spTraining' => $spTraining,
            'queueComplete' => $queueComplete
        ])->withMember($member);
    }

    public function skills ($member)
    {
        $member = Member::findOrFail($member);
        $skillz = collect();
        $member->skillz->load('group')->each(function ($skill) use ($skillz) {
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
        ])->withMember($member);
    }

    public function wallet_transactions ($member)
    {
        $member = Member::findOrFail($member);
        $transactions = $member->transactions()->paginate(25);
        return view('portal.wallet.transactions', [
            'transactions' => $transactions
        ])->withMember($member);
    }

    public function wallet_journal ($member)
    {
        $member = Member::findOrFail($member);
        $journal = $member->journals()->orderby('date', 'desc')->paginate(25);
        return view('portal.wallet.journals', [
            'journal' => $journal
        ])->withMember($member);
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
}
