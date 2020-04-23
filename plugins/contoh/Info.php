<?php
return [
    'name'          =>  'Contoh',
    'description'   =>  'Modul contoh saja',
    'author'        =>  'Sruu.pl',
    'version'       =>  '1.0',
    'compatibility' =>  '3.*',
    'icon'          =>  'code',                                 // Icon from http://fontawesome.io/icons/

    // Registering page for possible use as a homepage
    'pages'            =>  ['Sample Page' => 'sample'],

    'install'       =>  function () use ($core) {
    },
    'uninstall'     =>  function () use ($core) {
    }
];
