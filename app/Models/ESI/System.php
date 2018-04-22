<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'systems';
    public $incrementing = false;
    protected static $unguarded = true;
}
