<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

Route::get('/', '\App\Http\Controllers\Controller@homepage');
Route::get('now', '\App\Http\Controllers\Controller@now');

// Design
Route::get('design-blog', '\App\Http\Controllers\Controller@blog');


Route::get('contact', '\App\Http\Controllers\Controller@postContact');
Route::post('contact', '\App\Http\Controllers\Controller@postContact');