<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StravaSetup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('strava_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('strava_athlete_id')->unsigned()->unique();
            $table->string('username')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('profile_image_url');
            $table->text('access_token');
            $table->timestamp('activities_last_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('strava_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('strava_activity_id')->unsigned()->index();
            $table->integer('strava_user_id')->unsigned(); // Ours
            $table->string('activity_name')->index();
            $table->string('activity_type')->index();
            $table->integer('elevation_gain')->nullable();
            $table->bigInteger('distance')->unsigned()->nullable();
            $table->bigInteger('moving_time')->unsigned()->nullable();
            $table->timestamp('started_at')->inde();
            $table->timestamps();

            $table->foreign('strava_user_id')->references('id')->on('strava_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('strava_activities');
        Schema::drop('strava_users');
    }
}