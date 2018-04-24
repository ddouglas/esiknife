<?php

namespace ESIK\Composers;

use Auth;

class ScopeComposer
{
    public function compose ($view) {
        if (Auth::check()) {
            $view->with('scopes', Auth::user()->scopes);
        }
    }
}
