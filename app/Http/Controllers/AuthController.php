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

            $member = Member::firstOrNew(['id' => $getMemberData->id]);
            if ($member->exists) {
                Auth::login($member);
                Auth::user()->touch();
                if (Session::has('to')) {
                    $to = Session::get('to');
                    Session::forget('to');
                    return redirect($to);
                } else {
                    return redirect(route('dashboard'));
                }
            } else {
                $member->save();
                Auth::login($member);
                return redirect(route('welcome'));
            }
        }

        $state_hash = str_random(16);
        $state = collect([
            "redirectTo" => route('auth.login')
        ]);

        $params = collect([
            'response_type' => 'code',
            'redirect_uri' => route('sso.callback'),
            'client_id' => config('services.eve.sso.id'),
            'state' => $state_hash,
            'scope' => "publicData"
        ]);

        Session::put($state_hash, $state);
        $ssoUrl = config('services.eve.urls.sso.authorize')."?".http_build_query($params->toArray());
        return view("auth.login", [
           'ssoUrl' => $ssoUrl
       ]);
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();
        return redirect(route('home'));
    }
}
