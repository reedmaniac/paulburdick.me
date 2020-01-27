<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Strava\API\OAuth as StravaOAuth;
use Strava\API\Exception as StravaException;
use Strava\API\Client as StravaClient;
use Strava\API\Service\REST as StravaREST;
use League\OAuth2\Client\Token\AccessToken;

use App\Models\StravaUser;
use App\Models\StravaActivity;

class DeleteActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $strava_athlete_id;
    protected $strava_activity_id;

    /**
     * Create a new job instance.
     *
     * @param integer $strava_user_id
     * @return void
     */
    public function __construct($strava_athlete_id, $strava_activity_id)
    {
        $this->strava_athlete_id = $strava_athlete_id;
        $this->strava_activity_id = $strava_activity_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::debug(__CLASS__.' start');

        $user = $this->findStravaUser();

        if (empty($user)) {
            \Log::error(__CLASS__.': Unable to find Strava User for Strava Athlete ID: '.$this->strava_athlete_id);
            return;
        }

        \Log::debug(__CLASS__.': Deleting Activity for Strava Athlete ID: '.$this->strava_athlete_id);

        StravaActivity::where('strava_user_id', $user->id)
            ->where('strava_activity_id', $this->strava_activity_id)
            ->delete();

        \Log::debug(__CLASS__.' end');
    }

    /**
     * Find Strava User
     *
     * @return null|\App\Models\StravaUser
     */
    private function findStravaUser()
    {
        return StravaUser::where('strava_athlete_id', $this->strava_athlete_id)->firstOrFail();
    }
}
