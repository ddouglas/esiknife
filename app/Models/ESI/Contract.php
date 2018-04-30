<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

use ESIK\Models\ESI\Type;

class Contract extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'contracts';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'date_accepted', 'date_completed', 'date_expired', 'date_issued'
    ];

    public function getTypeAttribute($type)
    {
        return title_case(implode(" ", explode("_", $type)));
    }

    public function getStatusAttribute($status)
    {
        return ucfirst($status);
    }

    public function assignee()
    {
        return $this->morphTo('assignee', 'assignee_type', 'assignee_id', 'id');
    }

    public function acceptor()
    {
        return $this->morphTo('acceptor', 'acceptor_type', 'acceptor_id', 'id');
    }

    public function start ()
    {
        return $this->morphTo('start', 'start_location_type', 'start_location', 'id');
    }

    public function end ()
    {
        return $this->morphTo('end', 'end_location_type', 'end_location', 'id');
    }

    public function issuer()
    {
        return $this->hasOne(Character::class, 'id', 'issuer_id');
    }

    public function issuer_corp()
    {
        return $this->hasOne(Corporation::class, 'id', 'issuer_corporation_id');
    }

    public function items()
    {
        return $this->belongsToMany(Type::class, 'contract_items', 'id', 'type_id')->withPivot('record_id', 'quantity', 'is_singleton', 'is_included');
    }
}
