<?php

namespace ESIK\Models\ESI;

use Illuminate\Database\Eloquent\Model;

class MailHeader extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mail_headers';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'sent'
    ];

    public function members()
    {
        return $this->belongsToMany(MailHeader::class, 'member_mail_headers', 'mail_id', 'member_id')->withPivot(['labels', 'is_read']);
    }

    public function recipients ()
    {
        return $this->hasMany(MailRecipient::class, 'mail_id', 'id')->with('info');
    }

    public function sender ()
    {
        return $this->morphTo();
    }
}
