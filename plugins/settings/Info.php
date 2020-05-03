<?php

return [
    'name'          =>  'Pengaturan',
    'description'   =>  'Pengaturan umum Khanza LITE.',
    'author'        =>  'Basoro',
    'version'       =>  '1.0',
    'compatibility' =>  '3.*',
    'icon'          =>  'wrench',
    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `lite_options` (
            `id` int(10) NOT NULL,
            `module` varchar(10) NOT NULL,
            `field` varchar(50) NOT NULL,
            `value` varchar(200) DEFAULT NULL
          )");

        $core->db()->pdo()->exec("ALTER TABLE `lite_options` ADD PRIMARY KEY (`id`)");
        $core->db()->pdo()->exec("ALTER TABLE `lite_options` MODIFY `id` int(10) NOT NULL AUTO_INCREMENT");
        $core->db()->pdo()->exec("INSERT INTO `lite_options` (`module`, `field`, `value`) VALUES ('settings', 'version', '3.0')");

    },

];
