<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberWalletJournal extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_wallet_journal';
    public $incrementing = false;
    protected static $unguarded = true;
}
