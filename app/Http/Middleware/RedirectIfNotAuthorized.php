<?php

namespace ESIK\Http\Middleware;

use Auth, Closure, Session;

class RedirectIfNotAuthorized
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $scope = null)
    {
        if ($request->user() && $request->route()->hasParameter('member')) {
            $memberId = (int)$request->route()->parameter('member');
            if ($request->user()->id != $memberId) {
                // Set User to variable and load accessee's keyed by their id
                $user = $request->user();
                $user->load('accessee');
                $accessees = $user->accessee->keyBy('id');
                if (!$accessees->has($memberId)) {
                    Session::flash('alert', [
                        'header' => "Unauthorized Request",
                        'message' => "You are not authorized to view data associated with that ID",
                        'type' => "danger",
                        'close' => 1
                    ]);
                    return redirect(route('dashboard'));
                }
                if (!is_null($scope)) {
                    $accessee = $accessees->get($memberId);
                    $accesseeScopes = collect(json_decode($accessees->get($memberId)->pivot->access, true));
                    if (!$accesseeScopes->containsStrict($scope)) {
                        Session::flash('alert', [
                            'header' => "Unauthorized Request",
                            'message' => "You are not authorized to view data associated with that page",
                            'type' => "danger",
                            'close' => 1
                        ]);
                        return redirect(route('dashboard'));
                    }
                }
            }
        }
        return $next($request);
    }
}
