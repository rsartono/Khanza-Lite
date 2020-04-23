<?php

	if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
		exit("Khanza LITE requires at least <b>PHP 5.5</b>");
	}

	ini_set('memory_limit', '-1');

	define('VERSION', '3.0');
	define('NAME', 'Khanza LITE');
  define('DBHOST', 'localhost');
  define('DBPORT', '3306');
  define('DBNAME', 'rshd_sik');
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
    9999 => 'settings',
		0 => 'dashboard',
		1 => 'pasien',
		2 => 'pendaftaran',
		3 => 'ralan',
		4 => 'apotek_ralan',
		5 => 'kasir_ralan',
		9996 => 'master',
		9998 => 'users',
		9997 => 'modules',
	]));

	// Developer mode
	define('DEV_MODE', false);
