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

    public function items()
    {
        return $this->belongsToMany(Type::class, 'contract_items', 'contract_id', 'type_id')->withPivot('record_id', 'quantity', 'is_singleton', 'is_included');
    }
}
