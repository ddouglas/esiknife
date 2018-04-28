<?php

namespace ESIK\Http\Controllers;

use Auth, Request, Session;
use ESIK\Http\Controllers\SSOController;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->ssoCont = new SSOController;
    }

    public function index ()
    {
        return view('settings.index');
    }

    public function token ()
    {
        if (Request::isMethod('delete')) {
            $revoke = $this->ssoCont->revoke(Auth::user()->refresh_token);
            $status = $revoke->status;
            $payload = $revoke->payload;
            if (!$status) {
                Session::flash('alert', [
                    'name' => "Unable to delete tokem",
                    'message' => "We are unable to revoke your token at this time due to invalid response from CCP. Please try again in a few moments.",
                    'type' => "danger",
                    'close' => 1
                ]);
                return redirect(route('settings.token'));
            }
            Auth::user()->update([
                'disabled' => 1,
                'disabled_timestamp' => null,
                'access_token' => null,
                'refresh_token' => null,
                'expires' => null,
                'scopes' => null
            ]);
            Auth::logout();
            Session::flash('alert', [
                'header' => "Token Deleted Successfully",
                'message' => "Your token has been successfully deleted from the system. Please login to register a new token and continue using the site",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('auth.login'));
        }
        return view('settings.token');
    }
}
