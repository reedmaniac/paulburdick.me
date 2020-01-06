<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $first_name }} {{ $last_name }} :: Activities</title>

    <!-- Custom Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
    <link href='https://fonts.googleapis.com/css?family=Kaushan+Script' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700' rel='stylesheet' type='text/css'>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ elixir('css/all.css') }}">

    <meta name="description" content="Coder. Geek. Vagabond. Thru-Hiker. Lover of Knowledge. Pursuer of Fun.">
    <meta name="author" content="Paul Burdick">

    <meta property="og:site_name" content="Paul Burdick" />
	<meta property="og:description" content="Coder. Geek. Vagabond. Thru-Hiker. Lover of Knowledge. Pursuer of Fun." />

	<meta property="twitter:creator" content="reedmaniac" />

    <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/favicons/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/favicons/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/favicons/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/favicons/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/favicons/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/favicons/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/favicons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicons/android-chrome-192x192.png" sizes="192x192">
    <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="/favicons/manifest.json">
    <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="apple-mobile-web-app-title" content="reedmaniac">
    <meta name="application-name" content="reedmaniac">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-TileImage" content="/mstile-144x144.png">
    <meta name="theme-color" content="#ffffff">

</head>

<body id="page-top" class="index">

    <!-- Now Section -->
    <section>
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-left">
                    <h2 class="section-heading">Your Strava Activities</h2>
                    <h3>Total Elevation Gain: {{ $total_elevation }}m / {{ number_format($total_elevation*3.28084) }} ft</h3>
                    <h3>Left Until 500K: {{ number_format(500000 - $total_elevation*3.28084) }}ft</h3>

                    <br>
                    <table width="100%">
                        <thead>
                            <th>Name</th>
                            <th>Activity Start Time</th>
                            <th>Elevation Gain (m)</th>
                            <th>Elevation Gain (ft)</th>
                        </thead>
                        <tbody>
                            @foreach ($activities as $activity)
                                <tr>
                                    <td>{{ $activity->activity_name }}</td>
                                    <td>{{ $activity->started_at->setTimezone('MST')->toDayDateTimeString() }} MST</td>
                                    <td>{{ $activity->elevation_gain }}</td>
                                    <td>{{ number_format($activity->elevation_gain*3.28084) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>



    <script src="{{ elixir('js/app.js') }}"></script>

    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-69209507-1', 'auto');
      ga('send', 'pageview');

    </script>

</body>

</html>
