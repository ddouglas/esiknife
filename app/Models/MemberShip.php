<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberShip extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_ships';
    public $incrementing = false;
    protected static $unguarded = true;
}
