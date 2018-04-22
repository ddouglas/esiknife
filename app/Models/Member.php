<?php

namespace ESIK\Models;

use ESIK\Models\ESI\{Character, Type};

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

    public function skillz()
    {
        return $this->belongsToMany(Type::class, 'member_skillz', 'id', 'skill_id')->withPivot('active_skill_level','trained_skill_level', 'skillpoints_in_skill');
    }
    public function skill_queue()
    {
        return $this->belongsToMany(Type::class, 'member_skill_queues', 'id', 'skill_id')->withPivot('queue_position', 'finished_level', 'starting_sp', 'finishing_sp', 'training_start_sp', 'start_date', 'finish_date');
    }
}
