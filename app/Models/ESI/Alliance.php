<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class Alliance extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'alliances';
    public $incrementing = false;
    protected static $unguarded = true;

    public function assignee()
    {
        return $this->morphOne(Contract::class, 'assignee');
    }

    public function acceptor()
    {
        return $this->morphOne(Contract::class, 'acceptor');
    }
}
