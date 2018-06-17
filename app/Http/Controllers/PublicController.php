<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session;

class PublicController extends Controller
{
    public function home ()
    {
        return redirect(route('auth.login'));
    }
    public function about ()
    {
        return view('public.about');
    }
    public function donate ()
    {
        return view('public.donate');
    }
}
