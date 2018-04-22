<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session;

class PublicController extends Controller
{
    public function home ()
    {
        return redirect(route('auth.login'));
    }
}
