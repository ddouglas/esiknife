<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session, Validator;
use ESIK\Models\Member;
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
        dd("This is the Dashboard View with no associated view");
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
                return config('services.eve.scope_map')[$scope];
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
                dd($getMemberLocation);
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
                dd($getMemberClones);
                $status = $getMemberClones->status;
                $payload = $getMemberClones->payload;
                if (!$status) {
                    $alert->push("Unfortunately we were unable to query your wallet right now. If you checked the allow token refreshes checkbox, we will attempt to update this within five minutes.");
                }
            }
            return redirect(route('welcome'));
        }
        return view('portal.welcome');
    }
}
