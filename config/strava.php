<?php

return [

    'client_id' => env('STRAVA_CLIENT_ID'),
    'client_secret' => env('STRAVA_CLIENT_SECRET'),
    'redirect_uri' => 'strava/callback',
    'webhooks_uri' => 'strava/webhooks',
    'admin_access_code' => env('STRAVA_ADMIN_ACCESS_CODE'),
];
