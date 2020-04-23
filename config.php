<?php

	if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
		exit("OpenSIMRS requires at least <b>PHP 5.5</b>");
	}

	ini_set('memory_limit', '-1');

	define('VERSION', '3.0');
	define('NAME', 'OpenSIMRS');
  define('DBHOST', 'localhost');
  define('DBPORT', '3306');
  define('DBNAME', 'sik_coba');
  define('DBUSER', 'root');
  define('DBPASS', '');
	define('HOMEPAGE', 'dashboard');

  // URL Webapps
  define('WEBAPPS_URL', 'https://simrs.rshdbarabai.com/webapps');

	// Admin cat name
	define('ADMIN', 'admin');

	// Themes path
	define('THEMES', BASE_DIR . '/themes');

	// Modules path
	define('MODULES', BASE_DIR . '/plugins');

	// Uploads path
	define('UPLOADS', BASE_DIR . '/uploads');

	// Lock files
	define('FILE_LOCK', false);

	// Basic modules
	define('BASIC_MODULES', serialize([
    8 => 'settings',
		0 => 'dashboard',
		7 => 'users',
		6 => 'modules',
	]));

	// HTML beautifier
	define('HTML_BEAUTY', true);

	// Developer mode
	define('DEV_MODE', true);
