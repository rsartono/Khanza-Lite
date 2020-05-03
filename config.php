<?php

	if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
		exit("Khanza LITE requires at least <b>PHP 5.5</b>");
	}

	ini_set('memory_limit', '-1');

  define('DBHOST', 'localhost');
  define('DBPORT', '3306');
  define('DBUSER', 'root');
  define('DBPASS', '');
	define('DBNAME', 'sik');
	define('HOMEPAGE', 'dashboard');
	define('CEKSTATUSBAYAR', false);

	define('BpjsApiUrl', 'https://new-api.bpjs-kesehatan.go.id:8080/new-vclaim-rest/');
	define('ConsID', '');
	define('SecretKey', '');

  // URL Webapps
  define('WEBAPPS_URL', '');
	define('WEBAPPS_PATH', BASE_DIR . '/webapps');

	// Admin url path
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
		0 => 'dashboard',
		1 => 'pasien',
		2 => 'pendaftaran',
		//3 => 'ralan',
		//4 => 'apotek_ralan',
		//5 => 'kasir_ralan',
		9996 => 'master',
		9997 => 'modules',
		9998 => 'users',
		9999 => 'settings',
	]));

	// Developer mode
	define('DEV_MODE', true);
