<?php

namespace ESIK\Models\ESI;

use Carbon;
use Illuminate\Database\Eloquent\Model;

class CharacterCorporationHistory extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'character_corporation_histories';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $with = ['info'];

    protected $dates = [
        'start_date'
    ];

    public function info ()
    {
        return $this->hasOne(Corporation::class, 'id', 'corporation_id');
    }

    public function setStartDateAttribute($date)
    {
        $this->attributes['start_date'] = Carbon::parse($date);
    }
}
