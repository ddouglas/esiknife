<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session, Validator;
use ESIK\Models\{Member, MemberUrl, AccessGroup};
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
        if (Auth::user()->id == $memberId) {
            Session::flash('alert', [
                'header' => "Are you trying to cause a blackhole?",
                'message' => "You cannot use your own grant url on yourself. Bad Things happen when you do this. Please send this to another user. If you want to use your own grant url on an Alt, then Logout before clicking on the URL.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route('settings.access'));
        }
        $grant = $hash[1];
        $isGroup = false;
        if (starts_with($grant, "G-")) {
            $grant = AccessGroup::where(['creator_id' => $memberId, 'id' => $grant])->with('members')->firstOrFail();
            $isGroup = true;
        } else {
            $grant = MemberUrl::where(['id' => $memberId, 'hash' => $grant])->firstOrFail();
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
        }
        if (Request::isMethod('post')) {
            if (Session::has('to')) {
                if (starts_with(Session::get('to'), url('/settings/grant'))) {
                    Session::forget('to');
                }
            }
            $grantScopes = $grant->scopes->filter(function ($scope) {
                return Auth::user()->scopes->containsStrict($scope);
            });
            $attach = collect();
            $currentAccessors = Auth::user()->accessor->keyBy('id');
            if ($isGroup) {
                $grant->members->each(function ($member) use ($currentAccessors, $attach, $grantScopes) {
                    if (!$currentAccessors->has($member->id) && !$attach->has($member->id) && Auth::user()->id != $member->id) {
                        $attach->put($member->id, [
                            'access' => $grantScopes->toJson()
                        ]);
                    }
                });
            } else {
                $attach->put($grant->id, [
                    'access' => $grantScopes->toJson()
                ]);
            }
            Auth::user()->accessor()->attach($attach->toArray());
            Session::flash('alert', [
                'header' => "Access Granted",
                'message' => "You have successfully granted access to ". ($isGroup ? $grant->name : $grant->member->info->name) .".",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('settings.access'));
        }
        return view('settings.grant', [
            'grant' => $grant,
            'isGroup' => $isGroup
        ]);
    }

    public function groups()
    {
        if (Request::isMethod('post')) {
            $validator = Validator::make(Request::all(), [
                'name' => "required|min:5|max:32|unique:access_groups,name",
                'scopes' => "required|array",
                'description' => "sometimes|nullable|min:16|max:512"
            ],[
                'name.required' => "A name is required for the group",
                'name.min' => "The name must be at least :min characters long",
                'name.max' => "The name can be no longer than :max characters long",
                'description.min' => "The description must be at least :min characters long",
                'description.max' => "The description can be no longer than :max characters long",
                'scopes.required' => "The group must have atleast one group associated with it.",
                'scopes.array' => "The request was not formatted correctly. Please try again."
            ]);
            if ($validator->fails()) {
                return redirect(route('settings.groups'))->withInput()->withErrors($validator);
            }
            $scopes = collect(Request::get('scopes'))->keys();
            $scopes = collect(config('services.eve.scopes'))->only($scopes->toArray())->values();

            $id = "G-" . str_random(14);
            Auth::user()->groups()->create([
                'id' => $id,
                'name' => Request::get('name'),
                'description' => Request::has('description') ? Request::get('description') : null,
                'scopes' => $scopes->toJson()
            ]);
            $group = AccessGroup::find($id);
            $group->members()->attach(Auth::user()->id);
            return redirect(route('settings.group', [
                'group' => $id
            ]));
        }
        return view('settings.groups');
    }

    public function group(string $group)
    {
        $group = Auth::user()->groups()->where('id', $group)->with('members')->firstOrFail();
        if (Request::isMethod('delete')) {
            $group->delete();
            Session::flash('alert', [
                'header' => "Group Deleted Successfully",
                'message' => "The group " . $group->name . " has been deleted successfully",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('settings.groups'));
        }
        if (Request::isMethod('post')) {
            $validator = Validator::make(Request::all(), [
                'action' => "required|in:search,add,remove",
                'name' => "required_if:action,search|min:1",
                'id' => "required_if:action,add|numeric"
            ]);
            if ($validator->fails()) {
                return redirect(route('settings.group', ['group' => $group->id]))->withInput()->withErrors($validator);
            }
            $action = Request::get('action');

            if ($action === "search") {
                $search = $this->search(Request::get('name'));
                $status = $search->status;
                $payload = $search->payload;
                if (!$status) {
                    Session::flash('alert', [
                        'header' => "No Results Returned",
                        'message' => "We received 0 results for the term ". Request::get('char_name') . ". Please try again with a different or more refined  name",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('settings.group', ['group' => $group->id]));
                }
                return view('settings.group', [
                    'group' => $group,
                    'results' => collect($payload->response)->recursive()
                ]);
            }
            if ($action === "add") {
                $id = Request::get('id');
                $member = Member::find($id);
                if (is_null($member)) {
                    Session::flash('alert', [
                        'header' => "Invalid Selection",
                        'message' => "The character you selected does not have account registered with ESI Knife. Please have the character log into ESI Knife first, then attempt to add them to the group",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('settings.group', ['group' => $group->id]));
                }
                $memberOfGroup = $group->members->where('id', $id)->count();
                if ($memberOfGroup) {
                    Session::flash('alert', [
                        'header' => "Already Member of Group",
                        'message' => "The character that you selected, <strong>" . $member->info->name . "</strong>, is already a member of this group. Please do not do this. Only attempt to add members that are not apart of the group.",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('settings.group', ['group' => $group->id]));
                }
                $group->members()->attach($id);
                Session::flash('alert', [
                    'header' => "Member Added Successfully",
                    'message' => "The character that you selected, <strong>" . $member->info->name . "</strong>, has successfully been added to the group <strong>". $group->name . "</strong>",
                    'type' => 'success',
                    'close' => 1
                ]);
                return redirect(route('settings.group', ['group' => $group->id]));
            }
            if ($action === "remove") {
                $id = Request::get('id');
                $member = Member::find($id);
                if (is_null($member)) {
                    Session::flash('alert', [
                        'header' => "Invalid Selection",
                        'message' => "The character you selected does not have account registered with ESI Knife. Please have the character log into ESI Knife first, then attempt to add them to the group",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('settings.group', ['group' => $group->id]));
                }
                $group->members()->detach($id);
                Session::flash('alert', [
                    'header' => "Member Removed Successfully",
                    'message' => "The character that you selected, <strong>" . $member->info->name . "</strong>, has successfully been removed from the group <strong>". $group->name . "</strong>",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('settings.group', ['group' => $group->id]));
            }
        }
        return view('settings.group', [
            'group' => $group
        ]);
    }

    public function token ()
    {
        if (Request::isMethod('delete')) {
            $contracts = Auth::user()->contracts()->withCount('members')->chunk(50, function ($chunk) {
                $chunk->each(function ($contract) {
                    if ($contract->members_count <= 1) {
                        $contract->delete();
                    }
                });
            });
            $headers = Auth::user()->mail()->withCount('members')->chunk(50, function ($chunk) {
                $chunk->each(function ($header) {
                    if ($header->members_count <= 1) {
                        $header->delete();
                    }
                });
            });
            $revoke = $this->ssoCont->revoke(Auth::user()->refresh_token);
            echo collect($revoke)->recursive()->toJson();

            dd("Hi");
            Auth::user()->alts()->delete();
            Session::flash('alert', [
                'header' => "Token Deleted Successfully",
                'message' => "Your token has been successfully deleted from the system. Please login to register a new token and continue using the site",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('auth.login'));
        }
        if (Request::isMethod('post')) {
            $validator = Validator::make(Request::all(), [
                'scopes' => "array|required|min:1"
            ]);
            if ($validator->failed()) {
                return redirect(route('welcome'))->withErrors($validator);
            }
            $selected = collect(Request::get('scopes'))->keys();
            $authorized = $selected->map(function($scope) {
                return config('services.eve.scopes')[$scope]['scope'];
            });

            $authorized = $authorized->sort()->values()->implode(' ');

            $state_hash = str_random(16);
            $state = collect([
                "redirectTo" => route('settings.token'),
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
        if (Request::has('state')) {
            if (!Session::has(Request::get('state'))) {
                Session::flash('alert', [
                    "header" => "Unable to Verify Response",
                    'message' => "Something went wrong parsing the response from the API",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('settings.token'));
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
                return redirect(route('settings.token'));
            }
            if ($ssoResponse->get('CharacterID') !== Auth::user()->id) {
                Session::flash('alert', [
                    "header" => "Invalid Character Information Returned",
                    'message' => "That character information that we received back does not match that of the character that is current logged in. Please only authorize with the character that is currently logged into ESIKnife.",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('settings.token'));
            }
            $member = Member::firstOrNew(['id' => $ssoResponse->get('CharacterID')])->fill([
                'main' => Auth::user()->id,
                'scopes' => json_encode(explode(' ', $ssoResponse->get('Scopes'))),
                'access_token' => $ssoResponse->get('access_token'),
                'refresh_token' => $ssoResponse->get('refresh_token'),
                'expires' => Carbon::now()->addHours(48)
            ]);

            $member->save();
            Session::flash('alert', [
                "header" => "Token Updated Successfully",
                'message' => "Your token has been updated successfully and you have been redirected back to the dashboard. If you want, you can now refresh your characters data using the new scope set.",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('dashboard'));
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
                if ($scope === "accessee") {
                    if (Request::has('remove')) {
                        $remove = Request::get('remove');
                        $accessees = Auth::user()->accessee->keyBy('id');
                        if ($accessees->has($remove)) {
                            Auth::user()->accessee()->detach($remove);
                            Session::flash('alert', [
                                'header' => "Access Revoked Successfully",
                                'message' => "You have successfully revoke your access to that character with the id of  {$remove}.",
                                'type' => 'success',
                                'close' => 1
                            ]);
                            return redirect(route('settings.access'));
                        } else {
                            Session::flash('alert', [
                                'header' => "Unable to revoke access",
                                'message' => "We are unable to revoke your access to the character {$remove} because you do not have access to a character with that id.",
                                'type' => 'danger',
                                'close' => 1
                            ]);
                            return redirect(route('settings.access'));
                        }
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
                'name' => "sometimes|nullable|min:4|max:32",
                'scopes' => "required|array"
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
