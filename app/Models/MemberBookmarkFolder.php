<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBookmarkFolder extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_bookmark_folders';
    public $incrementing = false;
    protected static $unguarded = true;

    public function bookmarks()
    {
        return $this->hasMany(MemberBookmark::class, 'folder_id', 'folder_id');
    }
}
