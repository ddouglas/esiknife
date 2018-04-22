<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class MailRecipient extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mail_recipients';
    public $incrementing = false;
    protected static $unguarded = true;
}
