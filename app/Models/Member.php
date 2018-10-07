<?php

namespace ESIK\Models;

use ESIK\Models\ESI\{Character, MailHeader, MailingList, Type, Contract};

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'id';
    protected $table = 'members';
    public $incrementing = false;
    protected static $unguarded = true;

    protected $dates = [
        'expires'
    ];

    protected $with = ['info'];

    public function getScopesAttribute($scopes)
    {
        return collect(json_decode($scopes, true));
    }

    public function getRememberToken()
    {
        return null; // not supported
    }

    public function setRememberToken($value)
    {
        // not supported
    }

    public function getRememberTokenName()
    {
        return null; // not supported
    }

    /**
    * Overrides the method to ignore the remember token.
    */
    public function setAttribute($key, $value)
    {
        $isRememberTokenAttribute = $key == $this->getRememberTokenName();
        if (!$isRememberTokenAttribute)
        {
          parent::setAttribute($key, $value);
        }
    }

    // ****************************************************************************************************
    // *********************************** Member Data Relationships **************************************
    // ****************************************************************************************************
    public function info()
    {
        return $this->hasOne(Character::class, 'id', 'id');
    }

    public function accessor()
    {
        return $this->belongsToMany(Member::class, 'member_accesses', 'member_id', 'accessor_id')->withPivot('does_expire', 'expires', 'access');
    }

    public function accessee()
    {
        return $this->belongsToMany(Member::class, 'member_accesses', 'accessor_id', 'member_id')->withPivot('does_expire', 'expires', 'access');
    }

    public function alts()
    {
        return $this->hasMany(Member::class, 'main', 'main');
    }

    public function assets()
    {
        return $this->hasMany(MemberAsset::class, 'id', 'id');
    }

    public function bookmarks()
    {
        return $this->hasMany(MemberBookmark::class, 'id', 'id');
    }

    public function bookmarkFolders()
    {
        return $this->hasMany(MemberBookmarkFolder::class, 'id', 'id');
    }

    public function clone()
    {
        return $this->morphTo('clone', 'clone_location_type', 'clone_location_id', 'id');
    }

    public function contacts()
    {
        return $this->hasMany(MemberContact::class, 'id', 'id');
    }

    public function contact_labels()
    {
        return $this->hasMany(MemberContactLabels::class, 'id', 'id');
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'member_contracts', 'member_id', 'contract_id');
    }

    public function fittings ()
    {
        return $this->hasMany(Fitting::class, 'id');
    }

    public function groups()
    {
        return $this->hasMany(AccessGroup::class, 'creator_id', 'id');
    }

    public function group_member()
    {
        return $this->belongsToMany(Member::class, 'access_group_members', 'member_id', 'group_id');
    }

    public function implants()
    {
        return $this->belongsToMany(Type::class, 'member_implants', 'member_id', 'type_id');
    }

    public function jumpClones()
    {
        return $this->hasMany(MemberJumpClone::class, 'id', 'id');
    }

    public function jobs()
    {
        return $this->belongsToMany(JobStatus::class, 'member_jobs', 'member_id', 'job_id');
    }

    public function location ()
    {
        return $this->hasOne(MemberLocation::class, 'id', 'id')->with('info');
    }

    public function mail_labels()
    {
        return $this->hasOne(MemberMailLabel::class, 'id', 'id');
    }

    public function mailing_lists()
    {
        return $this->belongsToMany(MailingList::class, 'member_mailing_lists', 'member_id', 'mailing_list_id');
    }

    public function mail()
    {
        return $this->belongsToMany(MailHeader::class, 'member_mail_headers', 'member_id', 'mail_id')->withPivot('labels', 'is_read');
    }

    public function ship()
    {
        return $this->hasOne(MemberShip::class, 'id', 'id');
    }

    public function skillz()
    {
        return $this->belongsToMany(Type::class, 'member_skillz', 'id', 'skill_id')->withPivot('active_skill_level','trained_skill_level', 'skillpoints_in_skill');
    }
    public function skillQueue()
    {
        return $this->belongsToMany(Type::class, 'member_skill_queue', 'id', 'skill_id')->withPivot('queue_position', 'finished_level', 'level_start_sp', 'level_end_sp', 'training_start_sp', 'start_date', 'finish_date');
    }

    public function transactions () {
        return $this->hasMany(MemberWalletTransaction::class, 'id', 'id');
    }

    public function journals () {
        return $this->hasMany(MemberWalletJournal::class, 'id', 'id');
    }

    public function urls()
    {
        return $this->hasMany(MemberUrl::class, 'id', 'id');
    }
}
