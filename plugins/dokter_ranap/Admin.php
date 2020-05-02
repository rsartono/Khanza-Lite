<?php

namespace Plugins\Dokter_Ranap;

use Systems\AdminModule;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Manage' => 'manage',
        ];
    }

    public function getManage( $page = 1 )
    {

      //$this->_addHeaderFiles();
      $date = date('Y-m-d');
      $perpage = '10';
      $phrase = '';
      if(isset($_GET['s']))
        $phrase = $_GET['s'];

      // pagination
      $totalRecords = $this->db('reg_periksa')
        ->like('reg_periksa.no_rkm_medis', '%'.$phrase.'%')
        ->like('reg_periksa.no_rawat', '%'.$phrase.'%')
        ->where('status_lanjut', 'Ranap')
        ->where('reg_periksa.tgl_registrasi', $date)
        ->where('reg_periksa.kd_dokter', $_SESSION['opensimrs_username'])
        ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
        ->like('pasien.nm_pasien', '%'.$phrase.'%')
        ->toArray();
      $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), $perpage, url([ADMIN, 'dokter_ralan', 'manage', '%d']));
      $this->assign['pagination'] = $pagination->nav('pagination','5');
      $this->assign['totalRecords'] = $totalRecords;

      $offset = $pagination->offset();
      $rows = $this->db('reg_periksa')
        ->like('reg_periksa.no_rkm_medis', '%'.$phrase.'%')
        ->like('reg_periksa.no_rawat', '%'.$phrase.'%')
        ->where('status_lanjut', 'Ranap')
        ->where('reg_periksa.tgl_registrasi', $date)
        ->where('reg_periksa.kd_dokter', $_SESSION['opensimrs_username'])
        ->join('pasien', 'pasien.no_rkm_medis = reg_periksa.no_rkm_medis')
        ->like('pasien.nm_pasien', '%'.$phrase.'%')
        ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj = reg_periksa.kd_pj')
        ->offset($offset)
        ->limit($perpage)
        ->toArray();

      $this->assign['list'] = [];
      if (count($rows)) {
          foreach ($rows as $row) {
              $row = htmlspecialchars_array($row);
              $row['editURL'] = url([ADMIN, 'dokter_ralan', 'edit', convertNorawat($row['no_rawat'])]);
              $row['delURL']  = url([ADMIN, 'dokter_ralan', 'delete', convertNorawat($row['no_rawat'])]);
              $row['viewURL'] = url([ADMIN, 'dokter_ralan', 'view', convertNorawat($row['no_rawat'])]);
              $this->assign['list'][] = $row;
          }
      }

      return $this->draw('manage.html', ['dokter_ralan' => $this->assign]);

    }
}
