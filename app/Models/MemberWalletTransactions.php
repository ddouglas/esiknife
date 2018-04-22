<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberWalletTransactions extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_wallet_transactions';
    public $incrementing = false;
    protected static $unguarded = true;
}
