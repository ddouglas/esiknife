<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberJumpClone extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_jump_clones';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $with = ['location'];

    public function location ()
    {
        return $this->morphTo('location', 'location_type', 'location_id', 'id');
    }
}
