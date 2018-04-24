<?php

namespace ESIK\Composers;

use Auth, Route;

class ScopeComposer
{
    public function compose ($view) {
        if (Auth::check()) {
            $view->with('scopes', Auth::user()->scopes);
        }
        $view->with('currentRouteName', Route::currentRouteName());
    }
}
