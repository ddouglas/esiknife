<?php

namespace ESIK\Http\Controllers;

use Request, Session;
use ESIK\Models\Member;

class HackingController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        $member = Member::where('id',95923084)->with('location')->first();
        dd($member->location);
    }
}
