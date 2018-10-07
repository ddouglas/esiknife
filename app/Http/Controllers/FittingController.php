<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Http\Controllers\DataController;

class FittingController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
    }

    public function list()
    {
        $fittings = Auth::user()->load('fittings.items');
        return view('portal.fittings.list');
    }

    public function load()
    {
        return view('portal.fittings.load');
    }
}
