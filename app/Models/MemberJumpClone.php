<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberJumpClone extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_jump_clones';
    public $incrementing = false;
    protected static $unguarded = true;

    public function clone_info ()
    {
        return $this->morphTo('clone_info', 'location_type', 'location_id', 'id');
    }
}
