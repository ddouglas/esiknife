<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\ESI\System;

class MemberLocation extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_locations';
    public $incrementing = false;
    protected static $unguarded = true;

    public function info ()
    {
        return $this->morphTo('info', 'location_type', 'location_id', 'id');
    }

    public function system ()
    {
        return $this->hasOne(System::class, 'id', 'solar_system_id');
    }
}
