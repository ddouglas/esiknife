<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\ESI\Type;

class MemberShip extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_ships';
    public $incrementing = false;
    protected static $unguarded = true;

    public function type ()
    {
        return $this->hasOne(Type::class, 'id', 'type_id');
    }
}
