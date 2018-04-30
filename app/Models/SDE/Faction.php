<?php

namespace ESIK\Models\SDE;

use Illuminate\Database\Eloquent\Model;

class Faction extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'factions';
    public $incrementing = false;
    protected static $unguarded = true;

    public function contact_info()
    {
        return $this->morphOne(MemberContact::class, 'info');
    }
}
