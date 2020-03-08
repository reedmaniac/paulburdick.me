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

class FetchAthleteActivities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $strava_user_id;

    /**
     * Create a new job instance.
     *
     * @param integer $strava_user_id
     * @return void
     */
    public function __construct($strava_user_id)
    {
        $this->strava_user_id = $strava_user_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->findStravaUser($this->strava_user_id);

        if (is_null($user->activities_last_checked_at)) {
            $after = (new Carbon('2020-01-01 00:00'))->timestamp;
        } else {
            $after = (new Carbon($user->activities_last_checked_at))->subDays(7)->timestamp;
        }

        $page = 1;
        $max_pages = 5;

        \Log::debug('FetchAthleteActivities for '.$user->username);

        while (true) {
            $activities = $this->getActivities(
                $user->access_token->getToken(),
                $after,
                $page
            );

            \Log::debug('FetchAthleteActivities: getActivities', (array) $activities);

            foreach ($activities as $activity) {
                StravaActivity::updateOrCreate(
                    ['strava_user_id' => $activity['strava_user_id'], 'strava_activity_id' => $activity['strava_activity_id']],
                    $activity
                );
            }

            if ($activities->count() < 200 || $page > $max_pages) {
                break;
            }

            $page++;
        }

        $user->activities_last_checked_at = now();
        $user->save();
    }

    /**
     * Get Activities
     *
     * @param string $token The Access Token for Strava, should be current
     * @param integer Unix timestamp
     * @param integer $page Which page to fetch
     * @return \Illuminate\Support\Collection
     */
    public function getActivities($token, $after, $page)
    {
        $before = null;
        $per_page = 200;

        // Returns array
        $original_activities = $this->stravaClient($token)->getAthleteActivities($before, $after, $page, $per_page);

        // \Log::debug('FetchAthleteActivities: Original Activities ', $original_activities);

        return collect($original_activities)->transform(function ($item) {
            return [
                'strava_user_id' => $this->strava_user_id,
                'strava_activity_id' => $item['id'],
                'activity_name' => $item['name'],
                'activity_type' => $item['type'],
                'elevation_gain' => $item['total_elevation_gain'],
                'distance' => $item['distance'],
                'moving_time' => $item['moving_time'],
                'elapsed_time' => $item['elapsed_time'],
                'started_at' => $item['start_date'],
            ];
        });
    }

    /**
     * Find Strava User
     *
     * @return null|\App\Models\StravaUser
     */
    private function findStravaUser($strava_user_id)
    {
        $strava_user = StravaUser::findOrFail($strava_user_id);

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
}
