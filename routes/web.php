<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', '\App\Http\Controllers\Controller@homepage');
Route::get('now', '\App\Http\Controllers\Controller@now');

// Design
Route::get('design-blog', '\App\Http\Controllers\Controller@blog');
Route::get('contact', '\App\Http\Controllers\Controller@postContact');
Route::post('contact', '\App\Http\Controllers\Controller@postContact');



/*
|--------------------------------------------------------------------------
| Strava Application
|--------------------------------------------------------------------------
*/
Route::get('strava', '\App\Http\Controllers\StravaController@index');
Route::get('strava/activities', '\App\Http\Controllers\StravaController@activities');

Route::get('strava/login', '\App\Http\Controllers\StravaController@login');
Route::get('strava/callback', '\App\Http\Controllers\StravaController@callback');
Route::get('strava/webhooks', '\App\Http\Controllers\StravaController@webhooks'); // GET request for Subscription Setup
Route::post('strava/webhooks', '\App\Http\Controllers\StravaController@webhooks'); // POST request to receive actual webhooks

// Endpoint to create new webhook
Route::get('strava/create-webook', '\App\Http\Controllers\StravaController@createWebhookSubscription');
Route::get('strava/view-webook', '\App\Http\Controllers\StravaController@viewWebhookSubscription');
Route::get('strava/delete-webook', '\App\Http\Controllers\StravaController@deleteWebhookSubscription');
