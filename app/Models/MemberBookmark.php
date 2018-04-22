<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBookmark extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_bookmarks';
    public $incrementing = false;
    protected static $unguarded = true;
}
