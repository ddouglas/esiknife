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

    public function system()
    {
        return $this->hasOne(System::class, 'id','location_id');
    }

    public function creator ()
    {
        return $this->hasOne(Character::class, 'id', 'creator_id');
    }

    public function type ()
    {
        return $this->hasOne(Type::class, 'id', 'item_type_id');
    }

}
