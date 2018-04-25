<?php

namespace ESIK\Models\SDE;

use Illuminate\Database\Eloquent\Model;

class Constellation extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'constellations';
    public $incrementing = false;
    protected static $unguarded = true;

    public function location()
    {
        return $this->morphOn(MemberBookmark::class, 'location');
    }
}
