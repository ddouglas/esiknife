<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Input, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Http\Controllers\{DataController, PortalController};

class AltController extends Controller
{
    public function __construct ()
    {
        $this->dataCont = new DataController;
        $this->portCont = new PortalController;
    }

    public function add ()
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

            $params = collect([
                'response_type' => 'code',
                'redirect_uri' => route('sso.callback'),
                'client_id' => config('services.eve.sso.id'),
                'state' => $state_hash,
                'scope' => $authorized
            ]);

            Session::put($state_hash, $state);
            $ssoUrl = config('services.eve.urls.sso.authorize')."?".http_build_query($params->toArray());
            return redirect($ssoUrl);
        }
        $scopes = collect(config('services.eve.scopes'))->keys();
        return view('portal.alts.add', [
            'scopes' => $scopes
        ]);
    }

    public function remove(int $id)
    {
        if ($id == Auth::user()->id) {
            Session::flash('alert', [
                'header' => "Unable to Main Character",
                'message' => "You're currently authenticated with the character that you are attempting to delete. If you're attempting to delete your account, this is not the correct way. Please go to the Settings Menu to delete your account.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route('dashboard'));
        }

        $alt = Auth::user()->alts()->where('id', $id)->first();
        if (is_null($alt)) {
            Session::flash('alert', [
                'header' => "Unable to Remove Alt",
                'message' => "Unable to remove alt that does not belong to the currently authenticated character",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route('dashboard'));
        }
        if (Request::isMethod('delete')) {
            $alt->delete();
            Session::flash('alert', [
                'header' => "Alt Removed Successfully",
                'message' => "You have successfully removed the alt ". $alt->info->name . " from your account",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('dashboard'));
        }
        return view('portal.alts.remove', [
            'alt' => $alt
        ]);
    }
}
