<?php

namespace Plugins\Pendaftaran;

use Systems\AdminModule;

/**
 * Sample admin class
 */
class Admin extends AdminModule
{
    /**
     * Module navigation
     * Items of the returned array will be displayed in the administration sidebar
     *
     * @return array
     */
    public function navigation()
    {
        return [
            'Manage' => 'manage',
        ];
    }

    /**
     * GET: /admin/pendaftaran/manage
     * Subpage method of the module
     *
     * @return string
     */
    public function getManage($page = 1)
    {
      $this->_addHeaderFiles();
      //$date = date('Y-m-d');
      $date = '2020-01-12';
      $perpage = '10';
      $phrase = '';
      if(isset($_GET['s']))
        $phrase = $_GET['s'];

      // pagination
      $totalRecords = $this->db()->pdo()->prepare("SELECT reg_periksa.* FROM reg_periksa, pasien WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi = '$date'");
      $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
      $totalRecords = $totalRecords->fetchAll();

      $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), $perpage, url([ADMIN, 'pendaftaran', 'manage', '%d']));
      $this->assign['pagination'] = $pagination->nav('pagination','5');
      $this->assign['totalRecords'] = $totalRecords;

      $offset = $pagination->offset();
      $query = $this->db()->pdo()->prepare("SELECT reg_periksa.*, pasien.nm_pasien, pasien.alamat, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab FROM reg_periksa, pasien, dokter, poliklinik, penjab WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND reg_periksa.kd_dokter = dokter.kd_dokter AND reg_periksa.kd_poli = poliklinik.kd_poli AND reg_periksa.kd_pj = penjab.kd_pj AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi = '$date' LIMIT $perpage OFFSET $offset");
      $query->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
      $rows = $query->fetchAll();

      $this->assign['list'] = [];
      if (count($rows)) {
          foreach ($rows as $row) {
              $row = htmlspecialchars_array($row);
              $this->assign['list'][] = $row;
          }
      }

      return $this->draw('manage.html', ['pendaftaran' => $this->assign]);

    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/pendaftaran/js/admin/pasiens.js');
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/pendaftaran/css/admin/pasiens.css');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'pendaftaran', 'css']));
        $this->core->addJS(url([ADMIN, 'pendaftaran', 'javascript']), 'footer');
    }

}
