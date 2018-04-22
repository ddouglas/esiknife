<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'characters';
    public $incrementing = false;
    protected static $unguarded = true;
}
