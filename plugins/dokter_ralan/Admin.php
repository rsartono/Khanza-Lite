<?php

namespace Plugins\Dokter_Ralan;

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

      $this->_addHeaderFiles();
      $date = date('Y-m-d');
      $perpage = '10';
      $phrase = '';
      if(isset($_GET['s']))
        $phrase = $_GET['s'];

      // pagination
      $totalRecords = $this->db('reg_periksa')
        ->like('reg_periksa.no_rkm_medis', '%'.$phrase.'%')
        ->like('reg_periksa.no_rawat', '%'.$phrase.'%')
        ->where('status_lanjut', 'Ralan')
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
        ->where('status_lanjut', 'Ralan')
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
              $row['viewURL'] = url([ADMIN, 'dokter_ralan', 'view', convertNorawat($row['no_rawat'])]);
              $this->assign['list'][] = $row;
          }
      }

      return $this->draw('manage.html', ['dokter_ralan' => $this->assign]);

    }

    public function getView($id)
    {
        $id = revertNorawat($id);
        $this->_addHeaderFiles();
        $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $id)->oneArray();
        $pasien = $this->db('pasien')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->oneArray();
        $count_ralan = $this->db('reg_periksa')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('status_lanjut', 'Ralan')->count();
        $count_ranap = $this->db('reg_periksa')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('status_lanjut', 'Ranap')->count();
        $this->assign['print_rm'] = url([ADMIN, 'dokter_ralan', 'print_rm', $reg_periksa['no_rkm_medis']]);

        if (!empty($reg_periksa)) {
            $this->assign['view'] = $reg_periksa;
            $this->assign['view']['pasien'] = $pasien;
            $this->assign['view']['count_ralan'] = $count_ralan;
            $this->assign['view']['count_ranap'] = $count_ranap;
            $this->assign['fotoURL'] = url(MODULES.'/dokter_ralan/img/'.$pasien['jk'].'.png');
            $this->assign['manageURL'] = url([ADMIN, 'dokter_ralan', 'manage']);
            $rows = $this->db('reg_periksa')
                ->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])
                ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
                ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                ->desc('tgl_registrasi')
                ->toArray();

            foreach ($rows as &$row) {
                $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->where('no_rawat', $row['no_rawat'])->oneArray();
                $diagnosa_pasien = $this->db('diagnosa_pasien')->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')->where('no_rawat', $row['no_rawat'])->toArray();
                $rawat_jl_dr = $this->db('rawat_jl_dr')->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')->where('no_rawat', $row['no_rawat'])->toArray();
                $detail_pemberian_obat = $this->db('detail_pemberian_obat')->join('databarang', 'databarang.kode_brng = detail_pemberian_obat.kode_brng')->where('no_rawat', $row['no_rawat'])->toArray();
                $catatan_perawatan = $this->db('catatan_perawatan')->where('no_rawat', $row['no_rawat'])->oneArray();
                $row['keluhan'] = $pemeriksaan_ralan['keluhan'];
                $row['suhu_tubuh'] = $pemeriksaan_ralan['suhu_tubuh'];
                $row['tensi'] = $pemeriksaan_ralan['tensi'];
                $row['nadi'] = $pemeriksaan_ralan['nadi'];
                $row['respirasi'] = $pemeriksaan_ralan['respirasi'];
                $row['tinggi'] = $pemeriksaan_ralan['tinggi'];
                $row['berat'] = $pemeriksaan_ralan['berat'];
                $row['gcs'] = $pemeriksaan_ralan['gcs'];
                $row['pemeriksaan'] = $pemeriksaan_ralan['pemeriksaan'];
                $row['rtl'] = $pemeriksaan_ralan['rtl'];
                $row['catatan_perawatan'] = $catatan_perawatan['catatan'];
                $row['diagnosa_pasien'] = $diagnosa_pasien;
                $row['rawat_jl_dr'] = $rawat_jl_dr;
                $this->assign['riwayat'][] = $row;
            }

            return $this->draw('view.html', ['dokter_ralan' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'dokter_ralan', 'manage']));
        }
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/dokter_ralan/js/admin/dokter_ralan.js');
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/dokter_ralan/css/admin/dokter_ralan.css');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'dokter_ralan', 'css']));
        //$this->core->addJS(url([ADMIN, 'dokter_ralan', 'javascript']), 'footer');
    }

}
