<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Http\Controllers\DataController;

class PortalController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
        $this->ssoCont = new SSOController;
    }

    public function dashboard ()
    {
        Auth::user()->load('accessee.info', 'alts.jobs');$allJobs = Auth::user()->alts->pluck('jobs')->flatten();
        $jobs = collect([
            'pending' => $allJobs->whereIn('status', ['queued', 'executing'])->count(),
            'finished' => $allJobs->whereIn('status', ['finished'])->count(),
            'failed' => $allJobs->whereIn('status', ['failed'])->count()
        ]);
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

            if ($action === "refresh") {
                if ($jobs->get('pending') > 0) {
                    Session::flash('alert', [
                        'header' => "Invalid Refresh Attempt",
                        'message' => "There are currently jobs for this account in Pending Status. Please wait until all jobs for this account currently in this queue have been processed before dispatching additional jobs.",
                        'type' => 'info',
                        'close' => 1
                    ]);
                    return redirect(route('dashboard'));
                }
                if (!Request::has('id')) {
                    Session::flash('alert', [
                        'header' => "Parameters Missing",
                        'message' => "Insufficient Parameters were provided to complete the refresh request. Please try again using the button on the dashboard.",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('dashboard'));
                }
                $targetID = Request::get('id');
                $alts = Auth::user()->alts->keyBy('id');
                if (!$alts->has($targetID)) {
                    Session::flash('alert', [
                        'header' => "Unauthorized Refresh Attempt",
                        'message' => "The character you are attempting to refresh is not a character associated with this the currently authenticated user. Please only attempt to refresh Ids associated with this character.",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('dashboard'));
                }
                $target = $alts->get($targetID);
                $refresh = $this->ssoCont->refresh($target);
                if (!$refresh->status) {
                    Session::flash('alert', [
                        'header' => "Unable to refresh token",
                        'message' => "We are unable to refresh your token at this time. Please navigate to the settings menu to update your token.",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('dashboard'));
                }
                $refresh = $this->dispatchJobs($target);
                Session::flash('alert', [
                    'header' => "Alt Refreshed Successfully",
                    'message' => "Jobs to refresh your character have been dispatched successfully. Please monitor the model to the right and refresh the page when the jobs are complete.",
                    'type' => 'success',
                    'close' => 1
                ]);
                return redirect(route('dashboard'));
            }
            if ($action === "swap_main") {
                $targetID = Request::get('id');
                Member::where('main', Auth::user()->main)->update([
                    'main' => $targetID
                ]);
                Session::flash('alert', [
                    'header' => "Main Swapped Successfully",
                    'message' => "You have successfully swapping your main.",
                    'type' => 'success',
                    'close' => 1
                ]);
                return redirect(route('dashboard'));
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
                return collect(config('services.eve.scopes'))->recursive()->where('key', $scope)->first()->get('scope');
            });


            $authorized = $authorized->sort()->values()->implode(' ');

            $state_hash = str_random(16);
            $state = collect([
                "redirectTo" => route('welcome'),
                "additionalData" => [
                    'authorizedScopesHash' => hash('sha1', $authorized),
                    'storeRefreshToken' => Request::has('storeRefreshToken')
                ]
            ])->recursive();


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
            $this->dataCont->dispatchJobs($member);

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
