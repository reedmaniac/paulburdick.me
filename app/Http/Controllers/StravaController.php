<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

use Strava\API\OAuth as StravaOAuth;
use Strava\API\Exception as StravaException;
use Strava\API\Client as StravaClient;
use Strava\API\Service\REST as StravaREST;
use League\OAuth2\Client\Token\AccessToken;

use App\Models\StravaUser;
use App\Models\StravaActivity;
use App\Jobs\DeleteActivity;
use App\Jobs\FetchActivity;
use App\Jobs\FetchAthleteActivities;

class StravaController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     *  OAuth Provider
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
        } else {
            \Log::debug('User exists, refreshing!');
            FetchAthleteActivities::dispatch($user->id);
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

        FetchAthleteActivities::dispatch($user->id);

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

        $strava_user = StravaUser::where('strava_athlete_id', $strava_athlete_id)->first();

        if (empty($strava_user)) {
            return null;
        }

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

        $activities = StravaActivity::where('strava_user_id', $user->id)->orderBy('started_at')->get();

        $vars['total_elevation'] = $activities->sum('elevation_gain');

        $vars['first_name'] = $user->first_name;
        $vars['last_name'] = $user->last_name;
        $vars['activities'] = $activities;

        return view('strava.activities', $vars);
    }

    /**
     * Handle Strava Webhooks
     *
     * @return string
     */
    public function webhooks(Request $request)
    {
        if ($request->input('hub_mode') == 'subscribe') {
            return $this->webhookCreationValidation($request);
        }

        // General debugging
        \Log::debug('Strava Webhook', $request->all());

        if ($request->input('object_type') == 'activity') {

            if ($request->input('aspect_type') == 'create' || $request->input('aspect_type') == 'update') {
                FetchActivity::dispatch($request->input('owner_id'), $request->input('object_id'));
            }

            if ($request->input('aspect_type') == 'delete') {
                DeleteActivity::dispatch($request->input('owner_id'), $request->input('object_id'));
            }

            return response()->json([], 200);
        }

        // We don't do anything for this yet.
        if ($request->input('object_type') == 'athlete') {
            return response()->json([], 200);
        }

        \Log::error('Invalid Object Type for Strava Webhook', $request->all());
        abort(400);
    }

    /**
     * Handle Strava Webhooks
     *
     * @return string
     */
    private function webhookCreationValidation(Request $request)
    {
        $hub_challenge = $request->input('hub_challenge');
        $hub_token = $request->input('hub_verify_token');

        if (empty($hub_token) || $hub_token != md5('2020-strava')) {
            \Log::error(sprintf('Invalid Hub Token: %s'), $hub_token);
            throw ValidationException::withMessages(['Invalid Verify Token']);
        }

        if (empty($hub_challenge)) {
            \Log::error(sprintf('Empty Hub Challenge: %s'), $hub_challenge);
            throw ValidationException::withMessages(['Empty Hub Challenge']);
        }

        return response()->json(['hub.challenge' => $hub_challenge], 200);
    }

    /**
     * Create Strava Webhook Subscription for this Site
     *
     * When URL is pinged, it will attempt to send a new Webook subscription request
     * to the Strava API.
     * - Example Request: https://a799b809.ngrok.io/strava/create-webook?admin_code=shaggy
     *
     *  @return string
     */
    public function createWebhookSubscription(Request $request)
    {
        if (empty(config('strava.admin_access_code')) || $request->input('admin_code') != config('strava.admin_access_code')) {
            abort(404);
        }

        $path = 'push_subscriptions';

        $parameters['query'] = [
            'client_id' => config('strava.client_id'),
            'client_secret' => config('strava.client_secret'),
            'callback_url' => secure_url(config('strava.webhooks_uri')),
            'verify_token' => md5('2020-strava')
        ];

        $response = $this->getResponse('POST', $path, $parameters);

        if (!isset($response[201])) {
            \Log::debug('Unable to create Strava Webhook subscriptions', $response);
            dd($response);
        }

        return "Subscription Created Successfully!";
    }

    /**
     * View Strava Webhook Subscription for this Site
     *
     *  @return string
     */
    public function viewWebhookSubscription(Request $request)
    {
        if (empty(config('strava.admin_access_code')) || $request->input('admin_code') != config('strava.admin_access_code')) {
            abort(404);
        }

        $path = 'push_subscriptions';

        $parameters['query'] = [
            'client_id' => config('strava.client_id'),
            'client_secret' => config('strava.client_secret'),
        ];

        $response = $this->getResponse('GET', $path, $parameters);

        if (!isset($response[0], $response[0]['id']) && !isset($response['id'])) {
            \Log::debug('Unable to find Strava Webhook subscription', $response);
            dd($response);
        }

        $subscription_id = (isset($response[0]))? $response[0]['id'] : $response['id'];

        Cache::forever('strava:subscription_id', $subscription_id);

        \Log::debug("Subscription for Webhook Found and Saved Successfully: ".$subscription_id);

        dd($response);

        return "Subscription for Webhook Found and Saved Successfully: ".$subscription_id;
    }

    /**
     * Delete Strava Webhook Subscription for this Site
     *
     *  @return string
     */
    public function deleteWebhookSubscription(Request $request)
    {
        if (empty(config('strava.admin_access_code')) || $request->input('admin_code') != config('strava.admin_access_code')) {
            abort(404);
        }

        if (!Cache::has('strava:subscription_id')) {
            abort(400, "No Subscription Set Up for Site");
        }

        $subscription_id = Cache::get('strava:subscription_id');

        $path = 'push_subscriptions/'.$subscription_id;

        $parameters['query'] = [
            'client_id' => config('strava.client_id'),
            'client_secret' => config('strava.client_secret'),
        ];

        $response = $this->getResponse('DELETE', $path, $parameters);

        if (isset($response[204])) {
            return "Subscription Deleted Successfully!";
        }

        \Log::debug('Problem deleting Strava subscription', $response);

        dd($response);
    }

  /**
   * Build Adaptor
   *
   * The Strava Library we use does not allow us to use its getResponse method
   * so we needed to create our own for subscription work
   *
   * @return \GuzzleHttp\Client
   */
    private function adapter()
    {
        return new \GuzzleHttp\Client(['base_uri' => 'https://www.strava.com/api/v3/']);
    }

  /**
   * Get an API request response and handle possible exceptions.
   *
   * @param string $method
   * @param string $path
   * @param array $parameters
   *
   * @return array|mixed|string
   */
    private function getResponse($method, $path, $parameters)
    {
        try {
            $response = $this->adapter()->request($method, $path, $parameters);
            return $this->getResult($response);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get a request result.
     * Returns an array with a response body or and error code => reason.
     * @param \GuzzleHttp\Psr7\Response $response
     * @return array|mixed
     */
    private function getResult($response)
    {
        $status = $response->getStatusCode();
        if ($status == 200) {
            return json_decode($response->getBody(), JSON_PRETTY_PRINT);
        } else {
            return [$status => $response->getReasonPhrase()];
        }
    }
}
