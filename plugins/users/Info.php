<?php

return [
    'name'          =>  'Pengguna',
    'description'   =>  'Pengelolaan akun pengguna.',
    'author'        =>  'Basoro',
    'version'       =>  '1.0',
    'compatibility' =>  '3.*',
    'icon'          =>  'user',

    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` int(10) NOT NULL,
            `username` varchar(50) NOT NULL,
            `role` varchar(50) NOT NULL DEFAULT 'admin',
            `access` varchar(250) NOT NULL DEFAULT 'all'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $core->db()->pdo()->exec("CREATE TABLE `login_attempts` (
            `ip`    TEXT NOT NULL,
            `attempts`  int(11) NOT NULL,
            `expires`   int(11) NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `remember_me` (
            `id` int(10) NOT NULL,
            `token` text NOT NULL,
            `user_id` varchar(50) NOT NULL,
            `expiry` int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $core->db()->pdo()->exec('ALTER TABLE `remember_me`
            ADD PRIMARY KEY (`id`);');

        $core->db()->pdo()->exec('ALTER TABLE `users`
            ADD PRIMARY KEY (`id`);');

        $core->db()->pdo()->exec('ALTER TABLE `remember_me`
            MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;');

        $core->db()->pdo()->exec('ALTER TABLE `users`
            MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;');

        $core->db()->pdo()->exec("INSERT INTO `users` (`username`, `role`, `access`)
            VALUES ('spv', 'admin', 'all')");

    }
];
