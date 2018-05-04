<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\ESI\Type;

class MemberAsset extends Model
{
    protected $primaryKey = 'item_id';
    protected $table = 'member_assets';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $casts = [
        'is_singleton' => 'boolean',
    ];

    protected $with = [
        'type.group'
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'id', 'id');
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'type_id');
    }

}
