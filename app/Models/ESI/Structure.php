<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\MemberLocation;

class Structure extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'structures';
    public $incrementing = false;
    protected static $unguarded = true;

    public function info ()
    {
        return $this->morphOne(MemberLocation::class, 'info');
    }

    public function clone ()
    {
        return $this->morphOne(Member::class, "clone");
    }

    public function jumpClones ()
    {
        return $this->morphOne(MemberJumpClones::class, "location");
    }

    public function location()
    {
        return $this->morphOn(MemberBookmark::class, 'location');
    }
}
