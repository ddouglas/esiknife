<?php

namespace ESIK\Models;

use ESIK\Models\ESI\Type;
use Illuminate\Database\Eloquent\Model;

class MemberWalletTransaction extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_wallet_transactions';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'date'
    ];

    protected $with = [
        'client', 'location', 'type'
    ];

    public function client()
    {
        return $this->morphTo('client', 'client_type', 'client_id', 'id');
    }

    public function location()
    {
        return $this->morphTo('location', 'location_type', 'location_id', 'id');
    }

    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'type_id');
    }

    public function journal ()
    {
        return $this->hasOne(MemberWalletJournal::class, 'journal_id', 'journal_ref_id');
    }

    public function getUnitPriceAttribute($price)
    {
        return number_format($price, 2). " ISK";
    }

    public function getQuantityAttribute($quantity)
    {
        return number_format($quantity, 0);
    }

    public function getCreditAttribute()
    {
        return number_format($this->getOriginal('unit_price') * $this->getOriginal('quantity'), 2) . " ISK";
    }

}
