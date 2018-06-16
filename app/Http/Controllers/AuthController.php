<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon,Request, Session;
use ESIK\Models\Member;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
    }

    public function login ()
    {
        if (Request::has('state') && Session::has(Request::get('state'))) {
            $ssoResponse = Session::get(Request::get('state'));
            Session::forget(Request::get('state'));
            $this->dataCont->disableJobDispatch();
            $getMemberData = $this->dataCont->getMemberData($ssoResponse->get('CharacterID'), true);
            if (!$getMemberData->status) {
                activity(__METHOD__)->withProperties($getMemberData->payload)->log($getMemberData->payload->message);
                Session::flash('alert', [
                    "header" => "Unable to Retrieve Member Data",
                    'message' => "Unable to verify member data, please try again later.",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('auth.login'));
            }
            $getMemberData = $getMemberData->payload;

            // $entities = collect();
            // $entities->push($getMemberData->id);
            // $entities->push($getMemberData->corporation_id);
            // if ($getMemberData->alliance_id !== null) {
            //     $entities->push($getMemberData->alliance_id);
            // }
            // $entities = $entities->toArray();
            // $bl = BlackList::whereIn('id', $entities)->with('entity')->get()->keyBy('id');
            // if ($bl->isNotEmpty()){
            //     Session::flash('alert', [
            //        "header" => "Login Attempt Revoked",
            //        'message' => "One or more of the entities that you are associated with has been banned from using this applications.",
            //        'type' => 'danger',
            //        'close' => 1
            //     ]);
            //     return redirect(route('auth.login'));
            // }

            $member = Member::firstOrNew(['id' => $getMemberData->id]);
            if ($member->exists) {
                if ($member->disabled) {
                    return redirect(route('welcome', ['returning']));
                } else {
                    Auth::login($member);
                    if (Session::has('to')) {
                        $to = Session::get('to');
                        Session::forget('to');
                        return redirect($to);
                    } else {
                        return redirect(route('dashboard'));
                    }
                }
            } else {
                return redirect(route('welcome'));
            }
        }
        $state_hash = str_random(16);
        $state = collect([
            "redirectTo" => "auth.login"
        ]);
        Session::put($state_hash, $state);
        $ssoUrl = config("services.eve.urls.sso")."/oauth/authorize?response_type=code&redirect_uri=" . route(config('services.eve.sso.callback')) . "&client_id=".config('services.eve.sso.id')."&state={$state_hash}";
        return view("auth.login", [
           'ssoUrl' => $ssoUrl
       ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect(route('home'));
    }
}
