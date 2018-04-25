<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\ESI\{Character, System, Type};

class MemberBookmark extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_bookmarks';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $with = ['location', 'creator', 'type'];

    public function location()
    {
        return $this->morphTo('location', 'location_type', 'location_id', 'id');
    }

    public function creator ()
    {
        return $this->morphTo('creator', 'creator_type', 'creator_id', 'id');
    }

    public function type ()
    {
        return $this->hasOne(Type::class, 'id', 'item_type_id');
    }

}
