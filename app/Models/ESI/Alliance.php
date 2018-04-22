<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class Alliance extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'alliances';
    public $incrementing = false;
    protected static $unguarded = true;
}
