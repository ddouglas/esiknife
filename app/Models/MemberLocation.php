<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberLocation extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_locations';
    public $incrementing = false;
    protected static $unguarded = true;
}
