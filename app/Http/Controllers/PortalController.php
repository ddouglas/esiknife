<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Models\SDE\Group;
use ESIK\Models\ESI\Type;
use ESIK\Http\Controllers\DataController;

class PortalController extends Controller
{

    public $dataCont;

    public function __construct()
    {
        $this->dataCont = new DataController;
    }
    public function dashboard ()
    {
        return view('portal.dashboard');
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
        return view('portal.skillz', [
            'skillz' => $skillz
        ]);
    }

    public function bookmarks () {
        Auth::user()->load('bookmarks.system', 'bookmarks.creator', 'bookmarks.type', 'bookmarkFolders');
        return view('portal.bookmarks');
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
        return view('portal.flyable', [
            'flyable' => $flyable
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
        $lastSkill = Auth::user()->skillQueue->last();
        $queueComplete = "No Skills are currently training";
        if (!is_null($lastSkill->pivot->finish_date)) {
            $queueComplete = Carbon::parse($lastSkill->pivot->finish_date)->toDayDateTimeString();
        }
        return view('portal.queue', [
            'groupsTraining' => $groupsTraining,
            'spTraining' => $spTraining,
            'queueComplete' => $queueComplete
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
            if ($scopes->contains('esi-wallet.read_character_wallet.v1')) {
                $getMemberWallet = $this->dataCont->getMemberWallet($member);
                $status = $getMemberWallet->status;
                $payload = $getMemberWallet->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains('esi-skills.read_skills.v1')) {
                $getMemberSkillz = $this->dataCont->getMemberSkillz($member);
                $status = $getMemberSkillz->status;
                $payload = $getMemberSkillz->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your skills right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains('esi-skills.read_skillqueue.v1')) {
                $getMemberSkillQueue = $this->dataCont->getMemberSkillqueue($member);
                $status = $getMemberSkillQueue->status;
                $payload = $getMemberSkillQueue->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your skills right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains('esi-bookmarks.read_character_bookmarks.v1')) {
                $getMemberBookmarks = $this->dataCont->getMemberBookmarks($member);
                $status = $getMemberBookmarks->status;
                $payload = $getMemberBookmarks->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your bookmarks right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains('esi-location.read_ship_type.v1')) {
                $getMemberShip = $this->dataCont->getMemberShip($member);
                $status = $getMemberShip->status;
                $payload = $getMemberShip->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains('esi-location.read_location.v1')) {
                $getMemberLocation = $this->dataCont->getMemberLocation($member, $scopes);
                $status = $getMemberLocation->status;
                $payload = $getMemberLocation->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains('esi-location.read_location.v1')) {
                $getMemberLocation = $this->dataCont->getMemberLocation($member, $scopes);
                $status = $getMemberLocation->status;
                $payload = $getMemberLocation->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }

            if ($scopes->contains("esi-clones.read_clones.v1")) {
                $getMemberClones = $this->dataCont->getMemberClones($member, $scopes);
                $status = $getMemberClones->status;
                $payload = $getMemberClones->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
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
