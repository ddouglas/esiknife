<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\SDE\Group;

class Type extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'types';
    public $incrementing = false;
    protected static $unguarded = true;

    public function skillz()
    {
        return $this->belongsToMany(Type::class, 'type_skillz', 'type_id', 'id')->withPivot('value')->with('skillz');
    }

    public function attributes()
    {
        return $this->hasMany(TypeDogmaAttribute::class, 'type_id');
    }

    public function skillAttributes()
    {
        return $this->hasMany(TypeDogmaAttribute::class, 'type_id')->whereIn('attribute_id', config('services.eve.dogma.attributes.skillz.all'));
    }

    public function effects()
    {
        return $this->hasMany(TypeDogmaEffect::class, 'type_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class,  'group_id');
    }
}
