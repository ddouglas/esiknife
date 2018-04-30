<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberContact extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_contacts';
    public $incrementing = false;
    protected static $unguarded = true;

    public function info()
    {
        return $this->morphTo('info', 'contact_type', 'contact_id', 'id');
    }
}
