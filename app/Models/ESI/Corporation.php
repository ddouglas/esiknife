<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class Corporation extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'corporations';
    public $incrementing = false;
    protected static $unguarded = true;
}
