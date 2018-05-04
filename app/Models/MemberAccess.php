<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberAccess extends Model
{
    protected $primaryKey = 'member_id';
    protected $table = 'member_accesses';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'expires'
    ];
}
