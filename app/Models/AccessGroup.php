<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class AccessGroup extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'access_groups';
    public $incrementing = false;
    protected static $unguarded = true;

    public function getScopesAttribute($scopes)
    {
        return collect(json_decode($scopes, true));
    }

    public function members()
    {
        return $this->belongsToMany(Member::class, 'access_group_members', 'group_id', 'member_id');
    }
}
