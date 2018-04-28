<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberContactLabels extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_contact_labels';
    public $incrementing = false;
    protected static $unguarded = true;
}
