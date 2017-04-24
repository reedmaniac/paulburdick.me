var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {

      // Fonts need to be put in the correct spots for CSS versioning
	  mix.copy('./resources/assets/font-awesome/fonts/fontawesome-webfont.*', './public/build/fonts/');
	  mix.copy('./resources/assets/font-awesome/fonts/FontAwesome.otf', './public/build/fonts/');
	  mix.copy('./node_modules/bootstrap/dist/fonts/glyphicons-halflings-regular.*', './public/build/fonts/bootstrap/');

	  // The blog uses these since it cannot get versioned files...yet
	  mix.copy('./resources/assets/font-awesome/fonts/fontawesome-webfont.*', './public/fonts/');
	  mix.copy('./resources/assets/font-awesome/fonts/FontAwesome.otf', './public/fonts/');
	  mix.copy('./node_modules/bootstrap/dist/fonts/glyphicons-halflings-regular.*', './public/fonts/bootstrap/');

	  mix.sass([
	    	'app.scss',
	       ],
	       './public/css/sass.css')

	      .styles([
	         '../font-awesome/css/font-awesome.css',
	         './public/css/sass.css'
	      ],
	      './public/css/all.css')
	      .scripts([
			'./node_modules/jquery/dist/jquery.js',
			'./node_modules/bootstrap/dist/js/bootstrap.js',
			'./resources/assets/js/jquery.easing.js',
			'./resources/assets/js/classie.js',
			'./resources/assets/js/cbpAnimatedHeader.js',
			'./resources/assets/js/jqBootstrapValidation.js',
			'./resources/assets/js/contact_me.js',
			'./resources/assets/js/app.js'
		],
		'./public/js/app.js')
		.version(['public/css/all.css', 'public/js/app.js']);

	    // We combine our SASS CSS file into our all.css, but browsers complain without mapping file.
	    mix.copy('./public/css/sass.css.map', './public/build/css/');

});
