<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberAsset extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_assets';
    public $incrementing = false;
    protected static $unguarded = true;
}
