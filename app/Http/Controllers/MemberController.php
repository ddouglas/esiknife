<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Input, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Models\SDE\Group;
use ESIK\Models\ESI\{Station, System,Type};
use ESIK\Http\Controllers\DataController;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
    }

    public function overview (int $member)
    {
        $member = Member::with('info.history')->findOrFail($member);
        return view('portal.overview', [
            'member' => $member
        ]);
    }

    public function analyzer (int $member)
    {
        $member = Member::with('skillz')->findOrFail($member);

        $fittings = Auth::user()->fittings()->with('hull.group')->get();
        if ($fittings->isEmpty()) {
            Session::flash('alert', [
                'header' => "Insufficient Number of Fittings",
                'message' => "You must have atleast one fitting saved to the database before you can view that page.",
                'type' => "info",
                'close' => 1
            ]);
            return redirect(route('portal.skillz.list'));
        }
        $groups = $fittings->pluck('hull.group');
        if (Request::has('group')) {
            $group = (int) Request::get('group');
            $fittings = $fittings->where('hull.group_id', $group);
        }
        $skillz = $member->skillz->keyBy('id');
        foreach ($fittings as $fitting) {
            $missing = collect();
            foreach ($fitting->skills as $skill) {
                if (!$skillz->has($skill->get('id'))) {
                    $skill->put('current', 0);
                    $missing->put($skill->get('id'),$skill);
                } else if ($skillz->get($skill->get('id'))->pivot->active_skill_level < $skill->get('level')) {
                    $skill->put('current', $skillz->get($skill->get('id'))->pivot->active_skill_level);
                    $missing->put($skill->get('id'),$skill);
                }
            }
            $fitting->missing = $missing;
        }
        return view('portal.skillz.analyzer', [
            'fittings' => $fittings,
            'groups' => $groups
        ])->withMember($member);
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

    public function bookmarks (int $member)
    {
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
        $contracts = $member->contracts()->with('issuer', 'issuer_corp', 'acceptor', 'assignee', 'start', 'end')->paginate(25);
        return view('portal.contracts.list', [
            'contracts' => $contracts
        ])->withMember($member);
    }

    public function contract (int $member, int $id)
    {
        $member = Member::findOrFail($member);
        $contract = $member->contracts()->where('id', $id)->with('issuer.corporation', 'acceptor', 'assignee', 'start', 'end', 'items')->firstOrFail();
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

    public function mail (int $member, int $id)
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
                    'key' => strtolower(implode('_', explode(' ', $skill->group->name))),
                    'info' => collect([
                        'total_sp' => 0
                    ]),
                    'skills' => collect()
                ]));
            }
            $skillz->get($skill->group_id)->get('skills')->put($skill->skill_id, $skill);
            $skillz->get($skill->group_id)->get('info')->put('total_sp', $skillz->get($skill->group_id)->get('info')->get('total_sp') + $skill->pivot->skillpoints_in_skill);
        });
        $skillz = $skillz->sortBy('name');
        return view('portal.skillz.list', [
            'skillz' => $skillz,
            'total_count' => $member->skillz->count()
        ])->withMember($member);
    }

    public function transactions (int $member)
    {
        $member = Member::findOrFail($member);
        $transactions = $member->transactions()->paginate(25);
        return view('portal.wallet.transactions', [
            'transactions' => $transactions
        ])->withMember($member);
    }

    public function journal (int $member)
    {
        $member = Member::findOrFail($member);
        $journal = $member->journals()->orderby('date', 'desc')->paginate(25);
        return view('portal.wallet.journals', [
            'journal' => $journal
        ])->withMember($member);
    }
}
