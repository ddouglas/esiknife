<?php

namespace ESIK\Models;

use Illuminate\Database\Eloquent\Model;

class MemberWalletJournal extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'member_wallet_journals';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'date'
    ];

    public function getRefTypeAttribute($ref_type)
    {
        return title_case(implode(' ', explode('_', $ref_type)));
    }

    public function getBalanceAttribute($balance)
    {
        return number_format($balance, 2). " ISK";
    }

    public function getAmountAttribute($amount)
    {
        return number_format($amount, 2). " ISK";
    }
}
