<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberMailLabel extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_mail_labels';
    public $incrementing = false;
    protected static $unguarded = true;
}
