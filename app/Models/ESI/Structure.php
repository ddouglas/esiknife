<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'structures';
    public $incrementing = false;
    protected static $unguarded = true;
}
