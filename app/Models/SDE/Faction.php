<?php

namespace ESIK\Models\SDE;

use Illuminate\Database\Eloquent\Model;

class Faction extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'factions';
    public $incrementing = false;
    protected static $unguarded = true;
}
