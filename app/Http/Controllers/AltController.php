<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Input, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Http\Controllers\{DataController, PortalController};

class AltController extends Controller
{
    public function __construct ()
    {
        $this->dataCont = new DataController;
        $this->portCont = new PortalController;
    }

    public function add ()
    {
        $scopes = collect(config('services.eve.scopes'))->keys();
        return view('portal.alts.add', [
            'scopes' => $scopes
        ]);
    }
}
