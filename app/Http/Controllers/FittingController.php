<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Request, Session, Validator;
use ESIK\Models\{Fitting, Member};
use ESIK\Models\ESI\Type;
use ESIK\Http\Controllers\DataController;

use Illuminate\Support\Collection;

class FittingController extends Controller
{
    public function __construct()
    {
        $this->dataCont = new DataController;
        $this->ssoCont = new SSOController;
    }

    public function list()
    {
        if (Request::isMethod('delete')) {
            $id = Request::get('id');
            $fitting = Auth::user()->fittings()->where('id', $id)->first();
            $delete = $fitting->delete();
            Session::flash('alert', [
                "header" => "Fitting Successfully Deleted",
                'message' => $fitting->name ." fitting has been deleted successfully",
                'type' => 'success',
                'close' => 1
            ]);
            return redirect(route('fittings.list'));
        }

        $fittings = Auth::user()->fittings()->with('hull.group')->get();
        $groups = $fittings->pluck('hull.group');
        if (Request::has('group')) {
            $group = (int) Request::get('group');
            $fittings = $fittings->where('hull.group_id', $group);
        }
        return view('fittings.list', [
            'fittings' => $fittings,
            'groups' => $groups
        ]);
    }

    public function view (int $id)
    {
        $fitting = Auth::user()->fittings()->with('items')->findOrFail($id);
        $layout = $this->generateLayout($fitting->items);
        return view('fittings.view', [
            'fitting' => $fitting,
            'layout' => $layout
        ]);
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
            $groups = $fittings->pluck('hull.group')->unique('name')->sortBy('name')->values();
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
                $fitting = Fitting::firstOrNew([
                    'id' => $id,
                    'member_id' => Auth::user()->id
                ], [
                    'type_id' => $fitting->get('ship_type_id'),
                    'name' => $fitting->get('name'),
                    'description' => $fitting->get('description'),
                ]);
                $fitting->save();
                $itemIds = $items->pluck('type_id')->unique();
                $itemIds->push($fitting->type_id);
                $types = Type::whereIn('id', $itemIds->toArray())->with('skillz')->get();
                $missingItems = $itemIds->diff($types->pluck('id'));
                if ($missingItems->isNotEmpty()) {
                    foreach ($missingItems as $missingItem) {
                        $this->dataCont->getType($missingItem);
                    }
                }
                $fitItems = collect();
                $items->each(function ($item) use ($fitItems) {
                    $exists = $fitItems->where('type_id', $item->get('type_id'))->where('flag_id', $item->get('flag'));
                    if ($exists->isNotEmpty()) {
                        $exists->each(function ($fitItem, $key) use ($fitItems, $item) {
                            $fitItems->get($key)->put('quantity', $fitItem->get('quantity') + $item->get('quantity'));
                        });
                    } else {
                        $fitItems->push([
            				'type_id' => $item->get('type_id'),
            				'flag' => $item->get('flag'),
            				'quantity' => (int)$item->get('quantity')
            			]);
                    }
                });
                $skillz = collect($types->pluck('skillz')->flatten()->toArray())->recursive();
                $skillz = $this->generateSkillTree($skillz);
                $fitting->skills = $skillz->toJson();
                $fitting->save();
                $fitting->items()->attach($fitItems->toArray());
                Session::flash('alert', [
                    'header' => "Fitting ". $fitting->name . " saved successfully",
                    'message' => "The fitting ". $fitting->name . " has been successfully saved. To view the fitting, please the back to fittings button to the left.",
                    'type' => 'success',
                    'close' => 1
                ]);
                return redirect(route('fittings.load'));
            }
        }
        $fittings = collect(); $groups = collect();
        if (Session::has('fittings')) {
            if (Request::has('group')) {
                $group = (int) Request::get('group');
                $fittings = Session::get('fittings')->where('hull.group_id', $group);
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

    function generateSkillTree (Collection $skillz, Collection $results=null) :Collection
    {
        if (is_null($results)) {
            $results = collect();
        }
        foreach ($skillz as $skill) {
            if ($results->has($skill->get('id'))) {
                if ((int) $skill->get('pivot')->get('value') > $results->get($skill->get('id'))->get('level')) {
                    $results->get($skill->get('id'))->put('level', (int) $skill->get('pivot')->get('value'));
                }
            } else {
                $value = collect([
                    'id' => $skill->get('id'),
                    'name' => $skill->get('name'),
                    'level' => (int) $skill->get('pivot')->get('value'),
                    'rank' => $skill->get('rank')
                ]);
                $results->put($skill->get('id'), $value);
            }
            if ($skill->get('skillz')->isNotEmpty()) {
                $results = $this->generateSkillTree($skill->get('skillz'), $results);
            }
        }
        return $results;
    }

    public function generateLayout (Collection $items) :Collection
    {
        $layout = [];

        foreach ($items as $item) {
            if ($item->pivot->flag == 5)
            {
                $slot = 'cargo_bay';
                $layout[$slot][] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity,
                    'flag' => $item->pivot->flag
                ];
                if (!isset($array[5])) {
                    $array[5] = $slot;
                }
            } else if ($item->pivot->flag >= 27 && $item->pivot->flag <= 34)
            {
                $slot = 'high_slot';
                if (isset($layout[$slot][$item->id]['quantity'])) {
                    $quantity = $layout[$slot][$item->id]['quantity'] + $item->pivot->quantity;
                } else {
                    $quantity = $item->pivot->quantity;
                }
                $layout[$slot][$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'flag' => $item->pivot->flag
                ];
                if (!isset($array[1])) {
                    $array[1] = $slot;
                }
            } else if ($item->pivot->flag >= 19 && $item->pivot->flag <= 26)
            {
                $slot = 'mid_slot';
                if (isset($layout[$slot][$item->id]['quantity'])) {
                    $quantity = $layout[$slot][$item->id]['quantity'] + $item->pivot->quantity;
                } else {
                    $quantity = $item->pivot->quantity;
                }
                $layout[$slot][$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'flag' => $item->pivot->flag
                ];
                if (!isset($array[2])) {
                    $array[2] = $slot;
                }
            } else if ($item->pivot->flag >= 11 && $item->pivot->flag <= 18)
            {
                $slot = 'low_slot';
                if (isset($layout[$slot][$item->id]['quantity'])) {
                    $quantity = $layout[$slot][$item->id]['quantity'] + $item->pivot->quantity;
                } else {
                    $quantity = $item->pivot->quantity;
                }
                $layout[$slot][$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'flag' => $item->pivot->flag
                ];
                if (!isset($array[3])) {
                    $array[3] = $slot;
                }
            }  else if ($item->pivot->flag == 87)
            {
                $slot = 'drone_bay';
                $layout[$slot][] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->pivot->quantity,
                    'flag' => $item->pivot->flag
                ];
            } else if ($item->pivot->flag >= 92 && $item->pivot->flag <= 99)
            {
                $slot = 'rigs';
                if (isset($layout[$slot][$item->id]['quantity'])) {
                    $quantity = $layout[$slot][$item->id]['quantity'] + $item->pivot->quantity;
                } else {
                    $quantity = $item->pivot->quantity;
                }
                $layout[$slot][$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'flag' => $item->pivot->flag
                ];
                if (!isset($array[4])) {
                    $array[4] = $slot;
                }
            } else if ($item->pivot->flag >= 125 && $item->pivot->flag <= 132)
            {
                $slot = 'sub_systems';
                if (isset($layout[$slot][$item->id]['quantity'])) {
                    $quantity = $layout[$slot][$item->id]['quantity'] + $item->pivot->quantity;
                } else {
                    $quantity = $item->pivot->quantity;
                }
                $layout[$slot][$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'flag' => $item->pivot->flag
                ];
            }
        }
        $order = ['high_slot', 'mid_slot', 'low_slot', 'rigs', 'cargo_bay'];
        $ordered = [];
        foreach ($order as $key) {
            if (array_key_exists($key, $layout)) {
                $ordered[$key] = $layout[$key];
                unset($layout[$key]);
            }
        }
        return collect($ordered + $layout);
    }
}
