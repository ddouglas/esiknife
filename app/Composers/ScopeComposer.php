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
                $member = Member::findOrFail($memberId);
                if (Auth::user()->id == $memberId) {
                    $view->with('scopes', Auth::user()->scopes);
                } else {
                    $accessee = Auth::user()->accessee->keyBy('id')->get($memberId);
                    $accessableScopes = collect(json_decode($accessee->pivot->access, true));
                    $view->with('scopes', $accessableScopes);
                }
            } else {
                $view->with('scopes', Auth::user()->scopes);
            }
        }
        $view->with('currentRouteName', Route::currentRouteName());
    }
}
