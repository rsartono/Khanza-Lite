<?php

namespace Plugins\Dokter_Ralan;

use Systems\AdminModule;
use Systems\Lib\Fpdf\FPDF;
use Systems\Lib\Fpdf\PDF_MC_Table;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Index' => 'index',
        ];
    }

    public function getIndex( $page = 1 )
    {

      $this->_addHeaderFiles();
      $date = date('Y-m-d');
      $perpage = '10';
      $phrase = '';
      if(isset($_GET['s']))
        $phrase = $_GET['s'];

      // pagination
      $totalRecords = $this->db()->pdo()->prepare("SELECT reg_periksa.* FROM reg_periksa, pasien WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi = '$date' AND reg_periksa.kd_dokter = '$_SESSION[opensimrs_username]'");
      $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
      $totalRecords = $totalRecords->fetchAll();

      $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), $perpage, url([ADMIN, 'dokter_ralan', 'manage', '%d']));
      $this->assign['pagination'] = $pagination->nav('pagination','5');
      $this->assign['totalRecords'] = $totalRecords;

      $offset = $pagination->offset();
      $query = $this->db()->pdo()->prepare("SELECT reg_periksa.*, pasien.nm_pasien, pasien.alamat, dokter.nm_dokter, poliklinik.nm_poli, penjab.png_jawab FROM reg_periksa, pasien, dokter, poliklinik, penjab WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis AND reg_periksa.kd_dokter = dokter.kd_dokter AND reg_periksa.kd_poli = poliklinik.kd_poli AND reg_periksa.kd_pj = penjab.kd_pj AND (reg_periksa.no_rkm_medis LIKE ? OR reg_periksa.no_rawat LIKE ? OR pasien.nm_pasien LIKE ?) AND reg_periksa.tgl_registrasi = '$date' AND reg_periksa.kd_dokter = '$_SESSION[opensimrs_username]' LIMIT $perpage OFFSET $offset");
      $query->execute(['%'.$phrase.'%', '%'.$phrase.'%', '%'.$phrase.'%']);
      $rows = $query->fetchAll();

      $this->assign['list'] = [];
      if (count($rows)) {
          foreach ($rows as $row) {
              $row = htmlspecialchars_array($row);
              $row['editURL'] = url([ADMIN, 'dokter_ralan', 'edit', convertNorawat($row['no_rawat'])]);
              $row['viewURL'] = url([ADMIN, 'dokter_ralan', 'view', convertNorawat($row['no_rawat'])]);
              $this->assign['list'][] = $row;
          }
      }

      return $this->draw('index.html', ['dokter_ralan' => $this->assign]);

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
            $this->assign['manageURL'] = url([ADMIN, 'dokter_ralan', 'index']);

            $rows = $this->db()->pdo()->prepare("SELECT b.*, a.nm_poli, c.nm_dokter FROM poliklinik a, reg_periksa b, dokter c WHERE b.no_rkm_medis = '$reg_periksa[no_rkm_medis]' AND a.kd_poli = b.kd_poli AND b.kd_dokter = c.kd_dokter ORDER BY b.tgl_registrasi DESC");
            $rows->execute();
            $rows = $rows->fetchAll();

            foreach ($rows as &$row) {
                $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->where('no_rawat', $row['no_rawat'])->oneArray();
                $diagnosa_pasien = $this->db('diagnosa_pasien')->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')->where('no_rawat', $row['no_rawat'])->toArray();
                $rawat_jl_dr = $this->db('rawat_jl_dr')->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')->where('no_rawat', $row['no_rawat'])->toArray();
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
            redirect(url([ADMIN, 'dokter_ralan', 'index']));
        }
    }

    public function getPrint_rm($no_rkm_medis)
    {
      $pasien = $this->db('pasien')->where('no_rkm_medis', $no_rkm_medis)->oneArray();
      $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

      $pdf = new FPDF();
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(true, 10);
      $pdf->SetTopMargin(5);
      $pdf->SetLeftMargin(5);
      $pdf->SetRightMargin(5);

      $pdf->Image($logo, 5, 8, '18', '18', 'png');
      $pdf->SetFont('Arial', '', 24);
      $pdf->Text(30, 16, $this->core->getSettings('nama_instansi'));
      $pdf->SetFont('Arial', '', 12);
      $pdf->Text(30, 22, $this->core->getSettings('alamat_instansi').' - '.$this->core->getSettings('kabupaten'));
      $pdf->Text(30, 26, $this->core->getSettings('kontak').' - '.$this->core->getSettings('email'));

      $pdf->Output('rekam_medik_pasien_'.$pasien['no_rkm_medis'].'.pdf','I');

    }

    public function getResep()
    {
        header('Content-type: text/html');
        echo $this->draw(MODULES.'/dokter_ralan/view/admin/resep.html');
        exit();
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
