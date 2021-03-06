<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Client\Token\AccessToken;

class StravaUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'strava_users';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'activities_last_checked_at'];

    /**
     * Get the user's access token model
     *
     * @param  string  $value
     * @return \League\OAuth2\Client\Token\AccessToken
     */
    public function getAccessTokenAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * Set the user's access token.
     *
     * @param  \League\OAuth2\Client\Token\AccessToken  $value
     * @return void
     */
    public function setAccessTokenAttribute(AccessToken $value)
    {
        $this->attributes['access_token'] = serialize($value);
    }
}
