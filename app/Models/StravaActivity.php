<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StravaActivity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'strava_activities';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'started_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'strava_user_id',
        'strava_activity_id',
        'strava_athlete_id',
        'activity_name',
        'activity_type',
        'elevation_gain',
        'distance',
        'moving_time',
        'elapsed_time',
        'started_at'
    ];
}
