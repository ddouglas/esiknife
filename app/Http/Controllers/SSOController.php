<?php

namespace ESIK\Http\Controllers;

use ESIK\Models\Member;
use Carbon, Request, Session;
use ESIK\Http\Controllers\{DataController, HttpController};

class SSOController extends Controller
{

    public $dataCont, $httpCont;

    public function __construct()
    {
        $this->dataCont = new DataController;
        $this->httpCont = new HttpController;
    }

    // Callback receive the redirect from CCP and processes the authorization code and retrieves an access token.
    public function callback() {
        if (!Request::has('code') || !Request::has('state') || !Session::has(Request::get('state'))) {
            Session::flash('alert', [
                "header" => "SSO Error",
                'message' => "Valid Authorization Parameters are missing or invalid. Please try again.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route('auth.login'));
        }
        $data = collect();
        $stateSession = Session::get(Request::get('state'));
        Session::forget(Request::get('state'));
        //CSRF Passed, lets verify the Authorization Code now
        $verifyAuthCode = $this->dataCont->verifyAuthCode(Request::get('code'));
        if (!$verifyAuthCode->status) {
            Session::flash('alert', [
                "header" => "SSO Error",
                'message' => "Authorization with CCP SSO Failed. Please try again. If errors persists, contact David Davaham. These errors have been logged.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route($stateSession->get("redirectTo")));
        }
        $response = collect($verifyAuthCode->payload->response);

        //Authorization Code has been verified and we got back an Access Token and Refresh Token. Lets Verify those now and retrieve the some basic Character Data.
        $verifyAccessToken = $this->dataCont->verifyAccessToken($response->get('access_token'));
        if (!$verifyAccessToken->status) {
            Session::flash('alert', [
                "header" => "SSO Error",
                'message' => "Access Token Verification with CCP SSO Failed. Please try again. If errors persists, contact David Davaham. These errors have been logged.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route($stateSession->get("redirectTo")));
        }
        $response = $response->merge(collect($verifyAccessToken->payload->response));
        $state = str_random(16);

        $response = $response->merge($stateSession->get('additionalData'));
        Session::put($state, $response);
        return redirect(route($stateSession->get("redirectTo"), ['state' => $state]));
    }

    public function refresh(Member $member)
    {
        $payload = collect([
            'cid' => config('base.clientId'),
            'cs' => config('base.clientSecret'),
            'rt' => $member->refresh_token,
        ]);
		$postRefreshToken = $this->httpCont->postRefreshToken($payload);
        $status = $postRefreshToken->status;
        $payload = $postRefreshToken->payload;
		if (!$status) {
            $member->disabled = 1;
            $member->disabled_timestamp = Carbon::now();
			if (property_exists($payload->response, 'error') && in_array($payload->response->error, ["invalid_grant", "invalid_token"]) ) {
                $member->disabled_reason = $payload->response->error. " || ". $payload->response->error_description;
			}

		} else {
            $response = $payload->response;
            $member->access_token = $response->access_token;
            $member->refresh_token = $response->refresh_token;
            $member->expires = Carbon::now()->addSeconds($response->expires_in);
        }
        $member->save();
        return $postRefreshToken;

    }

    public function revoke ($token, $type="refresh_token")
    {
        $payload = collect([
            'cid' => config('base.clientId'),
            'cs' => config('base.clientSecret'),
            'rt' => $token,
            't' => $type
        ]);
        return $this->httpCont->postRevokeToken($payload);
    }
}
