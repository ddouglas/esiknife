<?php

namespace ESIK\Composers;

use Auth, Route;
use ESIK\Models\Member;

class ScopeComposer
{
    public function compose ($view) {
        if (Auth::check()) {
            if (Route::getFacadeRoot()->current()->hasParameter('member')) {
                $memberId = Route::getFacadeRoot()->current()->parameter('member');
                if (Auth::user()->id == $memberId) {
                    $scopes = Auth::user()->scopes;
                } else {
                    $member = Member::with('alts')->findOrFail($memberId);
                    $alt = $member->alts()->where('id', $memberId)->first();
                    $accessee = $member->accessee()->where('id', $memberId)->first();
                    if (!is_null($alt)) {
                        $scopes = $alt->scopes;
                    }
                    if (!is_null($accessee)) {
                        $scopes = collect(json_decode($accessee->pivot->access, true));
                    }
                }
            } else {
                $scopes = Auth::user()->scopes;
            }
            if (!isset($scopes)) {
                throw new \Exception("There are no scopes available for this view.");
            }
            $view->with('scopes', $scopes);
        }
        $view->with('currentRouteName', Route::currentRouteName());
    }
}
