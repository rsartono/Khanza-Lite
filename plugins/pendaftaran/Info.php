<?php

return [
    'name'          =>  'Pendaftaran',
    'description'   =>  'Pengelolaan data pendaftaran pasien.',
    'author'        =>  'Basoro',
    'version'       =>  '1.0',
    'compatibility' =>  '3.*',
    'icon'          =>  'user-plus',

    'install'       =>  function () use ($core) {
        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `pendaftaran` (
          `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
          `no_reg` text  NULL,
          `no_rawat` text NULL,
          `tgl_registrasi` text NULL,
          `jam_reg` text NULL,
          `kd_dokter` text NULL,
          `no_rm` text NULL,
          `kd_poli` text NULL,
          `p_jawab` text NULL,
          `alamatpj` text NULL,
          `hubunganpj` text NULL,
          `biaya_reg` text NULL,
          `stts` text NULL,
          `stts_daftar` text NULL,
          `status_lanjut` text NULL,
          `kd_pj` text NULL,
          `umurdaftar` text NULL,
          `sttsumur` text NULL,
          `status_bayar` text NULL,
          `status_poli` text NULL,
          `rujukan` text NULL
        )");

        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `poliklinik` (
          `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
          `kd_poli` text NULL,
          `nm_poli` text NULL,
          `biaya_registrasi` text NULL,
          `status` text NULL
        )");

        $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `jadwal` (
          `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
          `kd_dokter` text NULL,
          `kd_poli` text NULL,
          `hari` text NULL,
          `jam_mulai` text NULL,
          `jam_selesai` text NULL,
          `kuota` text NULL,
          `status` text NULL
        )");

        $core->db()->pdo()->exec("INSERT INTO `options` (`key`,`value`) VALUES
        ('stts','Belum,Sudah,Batal,Berkas Diterima,Dirujuk,Meninggal,Dirawat,Pulang Paksa'),
        ('stts_daftar','Lama,Baru'),
        ('status_lanjut','Ralan,Ranap'),
        ('sttsumur','Th,Bl,Hr'),
        ('status_bayar','Belum Bayar,Sudah Bayar'),
        ('status_poli','Lama,Baru')");


    },
    'uninstall'     =>  function () use ($core) {
        //$core->db()->pdo()->exec("DROP TABLE `pasien`");
    }
];
