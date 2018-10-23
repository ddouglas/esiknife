<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\ESI\Type;

class Fitting extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'fittings';
    public $incrementing = false;
    protected static $unguarded = true;

    public function hull()
    {
        return $this->hasOne(Type::class, 'id', 'type_id');
    }

    public function getSkillsAttribute($skills) {
        return collect(json_decode($skills))->recursive();
    }

    public function items () {
        return $this->belongsToMany(Type::class, 'fitting_items', 'fitting_id', 'type_id')->withPivot('flag', 'quantity');
    }
}
