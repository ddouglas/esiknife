<?php

namespace ESIK\Http\Controllers;

use Auth, Request, Session, Validator;
use ESIK\Models\{Member, MemberUrl};
use ESIK\Models\ESI\{Mailheader, Contract};
use ESIK\Http\Controllers\{DataController, SSOController};

class SettingController extends Controller
{
    public function __construct()
    {
        $this->ssoCont = new SSOController;
        $this->dataCont = new DataController;
    }

    public function index ()
    {
        return view('settings.index');
    }

    public function grant(string $hash)
    {
        $hash = explode(':', $hash);
        if (count($hash) != 2) {
            Session::flash('alert', [
                'header' => "Invalid Grant Format",
                'message' => "The submitted grant is invalid. Please check the format and try again. If error persits, contact the member who gave you the url.",
                'type' => "danger",
                'close' => 1
            ]);
            return redirect(route('dashboard'));
        }
        $memberId = $hash[0];
        $grant = MemberUrl::whereId($memberId)->whereHash($hash[1])->firstOrFail();
        $grantExists = Auth::user()->accessor()->where('accessor_id', $grant->id)->count();
        if ($grantExists > 0) {
            Session::flash('alert', [
                'header' => "Access Granted",
                'message' => "You have successfully granted access to ". $grant->member->info->name .".",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('settings.access'));
        }
        if (Request::isMethod('post')) {
            Auth::user()->accessor()->attach(collect([
                $grant->id => [
                    'access' => $grant->scopes->toJson()
                ]
            ]));
            Session::flash('alert', [
                'header' => "Access Granted",
                'message' => "You have successfully granted access to ". $grant->member->info->name .".",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('settings.access'));
        }
        return view('settings.grant', ['grant' => $grant]);
    }

    public function token ()
    {
        if (Request::isMethod('delete')) {
            if (!is_null(Auth::user()->refresh_token)) {
                $revoke = $this->ssoCont->revoke(Auth::user()->refresh_token);
                $status = $revoke->status;
                $payload = $revoke->payload;
                if (!$status) {
                    Session::flash('alert', [
                        'header' => "Unable to delete token",
                        'message' => "We are unable to revoke your token at this time due to invalid response from CCP. Please try again in a few moments.",
                        'type' => "danger",
                        'close' => 1
                    ]);
                    return redirect(route('settings.token'));
                }
            }
            $contracts = Auth::user()->contracts()->with('members')->chunk(50, function ($chunk) {
                $chunk->each(function ($contract) {
                    if ($contract->members->count() <= 1) {
                        $contract->delete();
                    }
                });
            });
            $headers = Auth::user()->mail()->with('members')->chunk(50, function ($chunk) {
                $chunk->each(function ($header) {
                    if ($header->members->count() <= 1) {
                        $header->delete();
                    }
                });
            });
            Auth::user()->delete();
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

    public function access()
    {
        if (Request::isMethod('post')) {
            if (Request::has('scope')) {
                $scope = Request::get('scope');
                if ($scope === "accessor") {
                    if (Request::has('action')) {
                        $action = Request::get('action');
                        if ($action === "search") {
                            $validator = Validator::make(Request::all(), [
                                'char_name' => "required|min:3|max:50"
                            ]);
                            if ($validator->fails()) {
                                return redirect(route('settings.access'))->withErrors($validator)->withInput();
                            }
                            $search = $this->search(Request::get('char_name'));
                            $status = $search->status;
                            $payload = $search->payload;
                            if (!$status) {
                                Session::flash('alert', [
                                    'header' => "No Results Returned",
                                    'message' => "We received 0 results for the term ". Request::get('char_name') . ". Please try again with a different or more refined  name",
                                    'type' => 'danger',
                                    'close' => 1
                                ]);
                                return redirect(route('settings.access'));
                            }
                            return view('settings.access', [
                                'results' => collect($payload->response)->recursive()
                            ]);
                        }
                        if ($action === "modify") {
                            $validator = Validator::make(Request::all(), [
                                'access' => "required|array|min:1"
                            ],[
                                'access.required' => "Each Accessor must have atleast one scope assigned to them. Delete the accessors access with the red trashcan if no access is desired",
                                'access.array' => "Invalid Format. DO NOT MODIFY FORM",
                                'access.min' => "Each Accessor must have atleast one scope assigned to them. Delete the accessors access with the red trashcan if no access is desired"
                            ]);
                            if ($validator->fails()) {
                                return redirect(route('settings.access'))->withErrors($validator)->withInput();
                            }
                            $access = collect(Request::get('access'))->recursive();
                            $access->each(function ($scopes, $charId) use ($access) {
                                $scopes->keys()->each(function ($scope) use ($charId, $access) {
                                    $access->get($charId)->push($scope);
                                    $access->get($charId)->forget($scope);
                                });
                            });
                            $access->each(function ($scopes, $accessorId) {
                                Auth::user()->accessor()->updateExistingPivot($accessorId, [
                                    'access' => $scopes->toJson()
                                ]);
                            });
                            return redirect(route('settings.access'));
                        }
                    }
                    if (Request::has('select')) {
                        $selection = (int) Request::get('select');
                        if (Auth::user()->id == $selection) {
                            Session::flash('alert', [
                                'header' => "You bein serious?",
                                'message' => "You cannot grant yourself access to your own data. Please don't do this again.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('settings.access'));
                        }
                        $selection = $this->dataCont->getCharacter($selection);
                        $isMember = Member::find($selection->payload->id);
                        if (is_null($isMember)) {
                            Session::flash('alert', [
                                'header' => "Not A Member",
                                'message' => "That character who you are attempting to grant access to is not a member of ESIKnife. Please have them register for ESI Knife, and then you will be able to share your data with them.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('settings.access'));
                        }
                        Auth::user()->accessor()->attach(collect([
                            $isMember->id => [
                                'expires' => now()->addDay(),
                                'access' => Auth::user()->scopes
                            ]
                        ]));
                        Session::flash('alert', [
                            'header' => "Access Granted",
                            'message' => "You have successfully granted access to ". $isMember->info->name .".",
                            'type' => 'success',
                            'close' => 1
                        ]);
                        return redirect(route('settings.access'));
                    }

                    if (Request::has('remove')) {
                        $selection = (int) Request::get('remove');
                        if (Auth::user()->id == $selection) {
                            Session::flash('alert', [
                                'header' => "You bein serious?",
                                'message' => "You cannot revoke access to your own data. Please don't do this again.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('settings.access'));
                        }
                        $isMember = Member::find($selection);
                        if (is_null($isMember)) {
                            Session::flash('alert', [
                                'header' => "Not A Member",
                                'message' => "That character who you are attempting to revoke access for is not a member of ESIKnife.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('settings.access'));
                        }
                        Auth::user()->accessor()->detach($selection);
                        Session::flash('alert', [
                            'header' => "Access Revoked",
                            'message' => "You have successfully revoke ". $isMember->info->name ." any access to your data via ESIKnife.",
                            'type' => 'success',
                            'close' => 1
                        ]);
                        return redirect(route('settings.access'));
                    }
                }
            }
        }
        Auth::user()->load('accessor', 'accessee');
        return view('settings.access');
    }

    public function urls ()
    {
        if (Request::isMethod('post')) {
            $validator = Validator::make(Request::all(), [
                'name' => "sometimes|nullable|min:4|max:32"
            ]);
            if ($validator->fails()) {
                return redirect(route('settings.urls'))->withInput()->withErrors($validator);
            }
            $scopes = collect(Request::get('scopes'))->keys();
            $scopes = collect(config('services.eve.scopes'))->only($scopes->toArray())->values();

            Auth::user()->urls()->create([
                'hash' => str_random(16),
                'name' => Request::get('name'),
                'scopes' => $scopes->toJson()
            ]);
            return redirect(route('settings.urls'));
        }
        if (Request::isMethod('delete')) {
            $hash = Request::get('hash');
            Auth::user()->urls()->where('hash', $hash)->delete();
            return redirect(route('settings.urls'));
        }
        return view ('settings.urls');
    }

    public function search($term) {
        $getSearch = $this->dataCont->getSearch("character", $term);
        $status = $getSearch->status;
        $payload = $getSearch->payload;

        if (!$status) {
            return $getSearch;
        }

        $results = collect($payload->response)->recursive();
        if ($results->has('character')) {
            $names = $this->dataCont->postUniverseNames($results->get('character'));
            return $names;
        } else {
            return (object)[
                'status' => false,
                'payload' => collect()
            ];
        }
    }

}
