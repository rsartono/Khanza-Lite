<?php

return [
    'name'          =>  'Modul',
    'description'   =>  'Pengelolaan modul.',
    'author'        =>  'Basoro',
    'version'       =>  '1.0',
    'compatibility' =>  '3.*',
    'icon'          =>  'plug',

    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `modules` (
                `id` int(11) NOT NULL,
                `dir` text,
                `sequence` text
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

        $core->db()->pdo()->exec('ALTER TABLE `modules`
            ADD PRIMARY KEY (`id`);');

        $core->db()->pdo()->exec('ALTER TABLE `modules`
            MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

    },
    'uninstall'     =>  function () use ($core) {
        $core->db()->pdo()->exec("DROP TABLE `modules`");
    }
];
