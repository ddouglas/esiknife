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
        $this->ssoCont = new SSOController;
    }

    public function list()
    {
        $fittings = Auth::user()->load('fittings.items');
        return view('fittings.list');
    }

    public function load()
    {
        if(!Auth::user()->scopes->contains(config('services.eve.scopes.readCharacterFittings')['scope'])) {
            Session::flash('alert', [
                "header" => "Valid Scope missing",
                'message' => "Your scope does not contain the scope needed to download fittings. Please go to the Settings Menu to modify your token.",
                'type' => 'danger',
                'close' => 1
            ]);
            return redirect(route('fittings.list'));
        }

        if (Request::isMethod('post')) {
            $this->ssoCont->refresh(Auth::user());
            $fittings = $this->dataCont->getMemberFittings(Auth::user());
            if (!$fittings->status) {
                Session::flash('alert', [
                    "header" => "Unable to load fittings",
                    'message' => "We apologize for the inconvience, but we are unable to load your fittings at this time.",
                    'type' => 'danger',
                    'close' => 1
                ]);
                return redirect(route('fittings.list'));
            }
            $fittings = $fittings->payload->recursive()->keyBy('fitting_id');
            $groups = $fittings->pluck('hull_group')->unique('name');
            Session::put('fittings', $fittings);
            Session::put('groups', $groups);
            return redirect(route('fittings.load'));
        }
        if (Request::has('action')) {
            $action = Request::get('action');
            if ($action === "clear") {
                Session::forget('fittings');
                Session::forget('groups');
                return redirect(route('fittings.load'));
            }
            if ($action === "save" && Request::has('id') && Session::has('fittings')) {
                $id = Request::get('id');
                $fittings = Session::get('fittings');
                if (!$fittings->has($id)) {
                    Session::flash('alert', [
                        "header" => "Unknown Fitting ID",
                        'message' => "The provided fitting id is not associated with any of the fittings downloaded from ESI",
                        'type' => 'danger',
                        'close' => 1
                    ]);
                    return redirect(route('fittings.load'));
                }
                $fitting = $fittings->get($id);
                $items = $fitting->get('items');
                $itemIds = $items->pluck('type_id')->unique();
                $types = Type::whereIn('id', $itemIds->toArray())->get();
            }
        }
        $fittings = collect(); $groups = collect();
        if (Session::has('fittings')) {
            if (Request::has('group')) {
                $group = (int) Request::get('group');
                $fittings = Session::get('fittings')->where('hull_group.id', $group);
            } else {
                $fittings = Session::get('fittings');
            }
        }
        if (Session::has('groups')) {
            $groups = Session::get('groups')->sortBy('name');
        }
        return view('fittings.load', [
            'fittings' => $fittings,
            'groups' => $groups
        ]);
    }
}
