<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class MailingList extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mailing_lists';
    public $incrementing = false;
    protected static $unguarded = true;

    public function mail_recipient ()
    {
        return $this->morphOne(MailRecipient::class, 'info');
    }
}
