<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberUrl extends Model
{
    protected $primaryKey = 'hash';
    protected $table = 'member_urls';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'expires'
    ];

    protected $with = [
        'member'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'id', 'id');
    }

    public function getScopesAttribute ($scopes)
    {
        return collect(json_decode($scopes, true))->recursive();
    }
}
