<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Strava\API\OAuth as StravaOAuth;
use Strava\API\Exception as StravaException;
use Strava\API\Client as StravaClient;
use Strava\API\Service\REST as StravaREST;
use League\OAuth2\Client\Token\AccessToken;

use App\Models\StravaUser;
use App\Models\StravaActivity;

class StravaController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     *  Authorization Page
     *
     *  @return string
     */
    private function buildStravaOAuth()
    {
        $config = array(
            'clientId'     => config('strava.client_id'),
            'clientSecret' => config('strava.client_secret'),
            'redirectUri'  => secure_url(config('strava.redirect_uri'))
        );

        return new StravaOAuth($config);
    }

    /**
     *  Index Page
     *
     *  @return string
     */
    public function index()
    {
        $user = $this->findStravaUserFromSession();

        if (empty($user)) {
            return redirect('strava/login');
        }

        return '<a href="'.secure_url('strava/activities').'">activities</a>';
    }

    /**
     *  Login Page
     *
     *  @return string
     */
    public function login()
    {
        try {
            print '<a href="'.$this->buildStravaOAuth()->getAuthorizationUrl(['scope' => 'activity:read_all']).'">Login via Strava</a>';
        } catch(StravaException $e) {
            print $e->getMessage();
        }
    }

    /**
     * Strava Callback
     *
     *  @return string
     */
    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return $this->login()->withErrors(['code' => trans('Unable to login via Strava as no code was provided.')]);
        }

        $oauth_data = $this->buildStravaOAuth()->getAccessToken('authorization_code', [
            'code' => $request->input('code')
        ]);

        $strava_data = $this->fetchAthleteFromToken($oauth_data->getToken());

        // Get Strava Athlete ID
        $strava_athlete_id = $strava_data['id'];

        $user = StravaUser::where('strava_athlete_id', $strava_athlete_id)->first();

        if (empty($user)) {
            $user = $this->createStravaUser($strava_data, $oauth_data);
        }

        session(['strava_athlete_id' => $user->strava_athlete_id]);

        return redirect('strava/activities');
    }

    /**
     * Create new Strava User in DB from new Strava Athlete Login
     *
     * @param array $strava_data The array we get when fetching athlete from Strava
     * @param \League\OAuth2\Client\Token\AccessToken
     * @return \App\Models\StravaUser
     */
    private function createStravaUser(array $strava_data, AccessToken $oauth_data)
    {
        $user = new StravaUser;
        $user->strava_athlete_id = $strava_data['id'];
        $user->username = $strava_data['username'];
        $user->first_name = $strava_data['firstname'];
        $user->last_name = $strava_data['lastname'];
        $user->profile_image_url = $strava_data['profile'];
        $user->access_token = $oauth_data;
        $user->save();

        return $user->refresh();
    }

    /**
     * Find Strava User from Session
     *
     * @return null|\App\Models\StravaUser
     */
    private function findStravaUserFromSession()
    {
        $strava_athlete_id = session('strava_athlete_id');

        if (empty($strava_athlete_id)) {
            return null;
        }

        $athlete = StravaUser::where('strava_athlete_id', $strava_athlete_id)->first();

        if (empty($athlete)) {
            return null;
        }

        $access_token = $athlete->access_token;

        if (empty($access_token)) {
            return null;
        }

        if (!$access_token->hasExpired()) {
            return $athlete;
        }

        $new_access_token = $this->buildStravaOAuth()->getAccessToken('refresh_token', [
            'refresh_token' => $access_token->getRefreshToken()
        ]);

        $athlete->access_token = $new_access_token;
        $athlete->save();

        return $athlete;
    }

    /**
     * Find Athlete from OAuth Data Token
     *
     *  @return string
     */
    private function fetchAthleteFromToken($token)
    {
        return $this->stravaClient($token)->getAthlete();
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
     * List Activities for Logged in User
     *
     *  @return string
     */
    public function activities()
    {
        $user = $this->findStravaUserFromSession();

        if (empty($user)) {
            return redirect('strava/login');
        }

        $token = $user->access_token->getToken();

        try {
            $before = null;
            $after = (new Carbon('2020-01-01 00:00'))->timestamp;
            $page = 1;
            $per_page = 200;

            $activities = $this->stravaClient($token)->getAthleteActivities($before, $after, $page, $per_page);

            $activities = collect($activities)->transform(function ($item) {
                return [
                    'strava_id' => $item['id'],
                    'strava_athlete_id' => $item['athlete']['id'],
                    'activity_name' => $item['name'],
                    'activity_type' => $item['type'],
                    'elevation_gain' => $item['total_elevation_gain'],
                    'distance' => $item['distance'],
                    'moving_time' => $item['moving_time'],
                    'activity_started_at' => new Carbon($item['start_date']),
                ];
            });
            print_r($activities);
        } catch(StravaException $e) {
            print $e->getMessage();
        }
    }
}
