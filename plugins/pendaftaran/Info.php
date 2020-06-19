<?php

return [
    'name'          =>  'Pendaftaran',
    'description'   =>  'Pengelolaan data pendaftaran pasien.',
    'author'        =>  'Basoro',
    'version'       =>  '1.1',
    'compatibility' =>  '3.*',
    'icon'          =>  'user-plus',

    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("INSERT INTO `lite_options` (`module`, `field`, `value`) VALUES ('pendaftaran', 'cekstatusbayar', '0')");
    }
];
