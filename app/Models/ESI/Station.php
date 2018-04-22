<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'stations';
    public $incrementing = false;
    protected static $unguarded = true;
}
