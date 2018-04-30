<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;
use ESIK\Traits\HasCompositePrimaryKey;

class MailRecipient extends Model
{
    use HasCompositePrimaryKey;

    protected $primaryKey = ['mail_id', 'recipient_id'];
    protected $table = 'mail_recipients';
    public $incrementing = false;
    protected static $unguarded = true;

    public function headers ()
    {
        return $this->hasOne(MailHeader::class, 'mail_id', 'id');
    }

    public function info ()
    {
        return $this->morphTo('info', 'recipient_type', 'recipient_id', 'id');
    }
}
