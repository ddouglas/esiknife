<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\MemberLocation;

class System extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'systems';
    public $incrementing = false;
    protected static $unguarded = true;

    public function info ()
    {
        return $this->morphOne(MemberLocation::class, 'location_info');
    }

    public function clone ()
    {
        return $this->morphOne(Member::class, "clone");
    }

    public function jumpClones ()
    {
        return $this->morphOne(MemberJumpClones::class, "jumpClones");
    }

    public function location()
    {
        return $this->morphOn(MemberBookmark::class, 'location');
    }
}
