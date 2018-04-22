<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class MailHeader extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mail_headers';
    public $incrementing = false;
    protected static $unguarded = true;
}
