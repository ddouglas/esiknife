<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Input, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Models\SDE\Group;
use ESIK\Models\ESI\{Station, System,Type};
use ESIK\Jobs\Members\GetMemberAssets;
use ESIK\Http\Controllers\DataController;

class PortalController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
    }

    public function dashboard ()
    {
        Auth::user()->load('accessee.info', 'alts.jobs');
        if (Request::has('action')) {
            $action = Request::get('action');
            if ($action === "delete_pending_grant") {
                if (Session::has('to')) {
                    if (starts_with(Session::get('to'), url('/settings/grant/'))) {
                        Session::forget('to');
                        Session::flash('alert', [
                            'header' => "Pending Grant Removed Successfully",
                            'message' => "The pending grant has been removed. If you still want to grant access to your data for the character that generated the URL, please navigate back to where you clicked on that URL, and click on it again.",
                            'type' => 'success',
                            'close' => 1
                        ]);
                        return redirect(route('dashboard'));
                    }
                }
            }
        }
        $allJobs = Auth::user()->alts->pluck('jobs')->flatten();
        $jobs = collect([
            'pending' => $allJobs->whereIn('status', ['queued', 'executing'])->count(),
            'finished' => $allJobs->whereIn('status', ['finished'])->count(),
            'failed' => $allJobs->whereIn('status', ['failed'])->count()
        ]);
        return view('portal.dashboard', [
            'jobs' => $jobs
        ]);
    }

    public function overview (int $member)
    {
        $member = Member::findOrFail($member);
        return view('portal.overview', [
            'member' => $member
        ]);
    }

    public function assets (int $member)
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

    public function bookmarks (int $member) {
        $member = Member::findOrFail($member);
        $member->load('bookmarkFolders');
        $bookmarks = $member->bookmarks;
        $uniqueLocations = $bookmarks->pluck('location')->keyBy('id');

        foreach ($uniqueLocations as $location) {
            $count = $member->bookmarks->where('location_id', $location->id)->count();
            $uniqueLocations->get($location->id)->count = $count;
        }

        $uniqueLocations = $uniqueLocations->sortByDesc('count');

        return view('portal.bookmarks', [
            'uniqueLocations' => $uniqueLocations,
            'bookmarks' => $bookmarks
        ])->withMember($member);
    }

    public function clones (int $member)
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

    public function contacts (int $member)
    {
        $member = Member::findOrFail($member);
        $member->load(['contacts' => function ($query) {
            $where = collect();
            if (Request::has('npc') && Request::get('npc')) {
                $where->push(collect(['contact_id', '>', 0]));
            } else {
                $where->push(collect(['contact_id', '>', 9000000]));
            }

            if (Request::has('standing')) {
                $where->push(collect(['standing', (int)Request::get('standing')]));
            }

            $query->where($where->toArray())->with('info');
        }] ,'contact_labels');
        return view('portal.contacts')->withMember($member);
    }

    public function contracts (int $member)
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
        $contract = $member->contracts()->where('id', $contract_id)->with('issuer.corporation', 'acceptor', 'assignee', 'start', 'end', 'items')->firstOrFail();
        return view('portal.contracts.view', [
            'contract' => $contract
        ])->withMember($member);
    }

    public function flyable (int $member)
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

    public function mails (int $member)
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

    public function queue (int $member)
    {
        $member = Member::findOrFail($member);
        $groupsTraining = collect();
        $spTraining = collect();

        $member->skillQueue->load('group')->each(function ($item) use ($spTraining, $groupsTraining) {
            if (!$groupsTraining->has($item->group_id)) {
                $item->training = 0;
                $groupsTraining->put($item->group_id, $item->group);
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

    public function skills (int $member)
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

    public function switch ()
    {
        if (!Request::has('to') || !Request::get('return')) {
            Session::flash('alert', [
               "header" => "Unable to Swap to Character",
               'message' => "That character has not registered for this application. Please register that character with this application before attempting to swap to the character",
               'type' => 'danger',
               'close' => 1
            ]);
            return redirect(route('dashboard'));
        }
        $member = Auth::user()->alts->keyBy('id')->get(Request::get('to'));
        if (is_null($member)) {
            Session::flash('alert', [
               "header" => "Unknown Alt",
               'message' => "Alt with ID ". Request::get('to') . " is not known to this system. please try again",
               'type' => 'danger',
               'close' => 1
            ]);
            return redirect(Request::get('return'));
        }
        Auth::logout();
        Auth::login($member);
        Session::flash('alert', [
           "header" => "Switch Successful",
           'message' => "You are now logged in as ". Auth::user()->info->name,
           'type' => 'info',
           'close' => 1
        ]);
        return redirect(Request::get('return'));
    }

    public function wallet_transactions (int $member)
    {
        $member = Member::findOrFail($member);
        $transactions = $member->transactions()->paginate(25);
        return view('portal.wallet.transactions', [
            'transactions' => $transactions
        ])->withMember($member);
    }

    public function wallet_journal (int $member)
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
                    'authorizedScopesHash' => hash('sha1', $authorized),
                    'storeRefreshToken' => Request::has('storeRefreshToken')
                ])
            ]);
            Session::put($state_hash, $state);
            $ssoUrl = config("services.eve.urls.sso")."/oauth/authorize?response_type=code&redirect_uri=" . route(config('services.eve.sso.callback')) . "&client_id=".config('services.eve.sso.id')."&state={$state_hash}&scope=".$authorized;
            return redirect($ssoUrl);
        }

        if (Request::has('state')) {
            if (!Session::has(Request::get('state'))) {
                Session::flash('alert', [
                    "header" => "Unable to Verify Response",
                    'message' => "Something went wrong parsing the response from the API",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('welcome'));
            }
            $ssoResponse = Session::get(Request::get('state'));
            Session::forget(Request::get('state'));
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
                    'message' => "We had an issue validating the scopes that you authenticated with. If the issue persists, please report the error via bitbucket using error id <strong>" . $payload->log_id . "</strong>",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('welcome'));
            }

            $memberData = $getMemberData->payload;
            $member = Member::firstOrNew(['id' => $memberData->id])->fill([
                'main' => Auth::check() ? Auth::user()->id : $payload->get('id'),
                'scopes' => json_encode(explode(' ', $ssoResponse->get('Scopes'))),
                'access_token' => $ssoResponse->get('access_token'),
                'refresh_token' => $ssoResponse->get('storeRefreshToken') ? $ssoResponse->get('refresh_token') : null,
                'expires' => Carbon::now()->addHours(48)
            ]);

            $member->save();
            $alert = collect();
            $now = now();
            $dispatchedJobs = collect();
            if ($member->scopes->contains(config('services.eve.scopes.readCharacterAssets'))) {
                $job = new \ESIK\Jobs\Members\GetMemberAssets($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterBookmarks'))) {
                $job = new \ESIK\Jobs\Members\GetMemberBookmarks($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterClones'))) {
                $job = new \ESIK\Jobs\Members\GetMemberClones($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterContacts'))) {
                $job = new \ESIK\Jobs\Members\GetMemberContacts($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterContracts'))) {
                $job = new \ESIK\Jobs\Members\GetMemberContracts($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterImplants'))) {
                $job = new \ESIK\Jobs\Members\GetMemberImplants($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterLocation'))) {
                $job = new \ESIK\Jobs\Members\GetMemberLocation($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterMails'))) {
                $job = new \ESIK\Jobs\Members\GetMemberMailLabels($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);

                $job = new \ESIK\Jobs\Members\GetMemberMailingLists($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);

                $job = new \ESIK\Jobs\Members\GetMemberMailHeaders($member->id, config('services.eve.mails.pages'));
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }
            if ($member->scopes->contains(config('services.eve.scopes.readCharacterShip'))) {
                $job = new \ESIK\Jobs\Members\GetMemberShip($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterSkills'))) {
                $job = new \ESIK\Jobs\Members\GetMemberSkillz($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterSkillQueue'))) {
                $job = new \ESIK\Jobs\Members\GetMemberSkillQueue($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }

            if ($member->scopes->contains(config('services.eve.scopes.readCharacterWallet'))) {
                $job = new \ESIK\Jobs\Members\GetMemberWallet($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);

                $job = new \ESIK\Jobs\Members\GetMemberWalletJournals($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);

                $job = new \ESIK\Jobs\Members\GetMemberWalletTransactions($member->id);
                $job->delay($now);
                $this->dispatch($job);
                $dispatchedJobs->push($job->getJobStatusId());
                $now = $now->addSeconds(1);
            }
            $member->jobs()->attach($dispatchedJobs->toArray());
            Session::flash('alert', [
                "header" => "Welcome to ESI Knife ". Auth::user()->info->name,
                'message' => "You account has been setup successfully. However, there is a lot of data we need to pull in from the API to properly display your profile to you, so bare with us while we talk with ESI and whip our slaves to get that data for you. It shouldn't take long. You can use the Job Status module to the right to check on the status of these jobs. When you have zero (0) pending jobs, it is okay to load up your character, otherwise, one of pages you visit may crash because we don't have all the data yet.",
                'type' => 'success',
                'close' => 1
            ]);
            if (Session::has('to')) {
                if (starts_with(Session::get('to'), url('/welcome'))) {
                    return redirect(route('dashboard'));
                }
                if (!starts_with(Session::get('to'), url('/settings/grant/'))) {
                    $to = Session::get('to');
                    Session::forget(Session::get('to'));
                    return redirect($to);
                }
            }
            return redirect(route('dashboard'));
        }
        return view('portal.welcome');
    }
}
