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

class FetchActivity implements ShouldQueue
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
        $activity = $this->getActivity();

        \Log::debug('PROCESSED ACTIVITY!', $activity);

        StravaActivity::updateOrCreate(
            ['strava_user_id' => $activity['strava_user_id'], 'strava_activity_id' => $activity['strava_activity_id']],
            $activity
        );

        \Log::debug(__CLASS__.' end');
    }

    /**
     * Get Activity from API, Process via Collection, and Return just the one
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActivity()
    {
        $user = $this->findStravaUser();

        if (is_null($user)) {
            throw new \Exception('No Strava User Found for Athlete ID '.$this->strava_athlete_id);
        }

        $token = $user->access_token->getToken();

        $activity = $this->stravaClient($token)->getActivity($this->strava_activity_id, false);

        \Log::debug('RETRIEVED ACTIVITY!', $activity);

        return collect([$activity])->transform(function ($item) use ($user) {
            return [
                'strava_user_id' => $user->id,
                'strava_activity_id' => $item['id'],
                'activity_name' => $item['name'],
                'activity_type' => $item['type'],
                'elevation_gain' => $item['total_elevation_gain'],
                'distance' => $item['distance'],
                'moving_time' => $item['moving_time'],
                'elapsed_time' => $item['elapsed_time'],
                'started_at' => new Carbon($item['start_date']),
            ];
        })->first();
    }

    /**
     * Find Athlete from OAuth Data Token
     *
     *  @return string
     */
    private function stravaClient($token)
    {
        $adapter = new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
        $service = new StravaREST($token, $adapter);
        return new StravaClient($service);
    }

    /**
     * Find Strava User
     *
     * @return null|\App\Models\StravaUser
     */
    private function findStravaUser()
    {
        $strava_user = StravaUser::where('strava_athlete_id', $this->strava_athlete_id)->firstOrFail();

        $access_token = $strava_user->access_token;

        if (empty($access_token)) {
            return null;
        }

        if (!$access_token->hasExpired()) {
            return $strava_user;
        }

        $new_access_token = $this->buildStravaOAuth()->getAccessToken('refresh_token', [
            'refresh_token' => $access_token->getRefreshToken()
        ]);

        $strava_user->access_token = $new_access_token;
        $strava_user->save();

        return $strava_user;
    }

    /**
     *  OAuth Provider
     *
     *  @return string
     */
    private function buildStravaOAuth()
    {
        $config = array(
            'clientId'     => config('strava.client_id'),
            'clientSecret' => config('strava.client_secret')
        );

        return new StravaOAuth($config);
    }
}
