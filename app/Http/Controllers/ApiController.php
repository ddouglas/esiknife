<?php

namespace ESIK\Http\Controllers;

use Log;
use ESIK\Models\Member;

class ApiController extends Controller
{
    public function status($id)
    {
        $member = Member::find($id);
        if (is_null($member)) {
            return response()->json([], 404);
        }
        Log::info('Return Job Status Count for Member '. $id, [$member]);
        return response()->json([
            'pending' => $member->jobs()->whereIn('status', ['queued', 'executing'])->count(),
            'finished' => $member->jobs()->whereIn('status', ['finished'])->count(),
            'failed' => $member->jobs()->whereIn('status', ['failed'])->count()
        ], 200);
    }
}
