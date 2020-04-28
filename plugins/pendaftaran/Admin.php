<?php

namespace Plugins\Pendaftaran;

use Systems\AdminModule;
use Systems\Lib\Fpdf\FPDF;
use Systems\Lib\Fpdf\PDF_MC_Table;

class Admin extends AdminModule
{
    private $assign = [];

    public function navigation()
    {
        return [
            'Kelola'    => 'manage',
            'Tambah Baru'                => 'add'
        ];
    }

    public function getManage($page = 1)
    {
      $this->_addHeaderFiles();
      $date = date('Y-m-d');
      //$date = '2020-01-12';
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
              $row['editURL'] = url([ADMIN, 'pendaftaran', 'edit', convertNorawat($row['no_rawat'])]);
              $row['delURL']  = url([ADMIN, 'pendaftaran', 'delete', convertNorawat($row['no_rawat'])]);
              $row['viewURL'] = url([ADMIN, 'pendaftaran', 'view', convertNorawat($row['no_rawat'])]);
              $row['print_buktidaftar'] = url([ADMIN, 'pendaftaran', 'print_buktidaftar', convertNorawat($row['no_rawat'])]);
              $this->assign['list'][] = $row;
          }
      }

      return $this->draw('manage.html', ['pendaftaran' => $this->assign]);

    }

    /**
    * add new pasien
    */
    public function getAdd()
    {
        $this->_addHeaderFiles();

        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'no_rkm_medis' => '',
              'nm_pasien' => '',
              'no_reg' => '',
              'no_rawat' => '',
              'tgl_registrasi' => '',
              'jam_reg' => '',
              'kd_dokter' => '',
              'no_rm' => '',
              'kd_poli' => '',
              'p_jawab' => '',
              'almt_pj' => '',
              'hubunganpj' => '',
              'biaya_reg' => '',
              'stts' => '',
              'stts_daftar' => '',
              'status_lanjut' => '',
              'kd_pj' => '',
              'umurdaftar' => '',
              'sttsumur' => '',
              'status_bayar' => '',
              'status_poli' => ''
            ];
        }

        $this->assign['form']['tgl_registrasi'] = date('Y-m-d');
        $this->assign['form']['jam_reg'] = date('H:i:s');
        $this->assign['poliklinik'] = $this->core->db('poliklinik')->where('status', '1')->toArray();
        $this->assign['dokter'] = $this->core->db('dokter')->toArray();
        $this->assign['status_lanjut'] = $this->_addEnum('reg_periksa', 'status_lanjut');
        $this->assign['status_bayar'] = $this->_addEnum('reg_periksa', 'status_bayar');
        $this->assign['penjab'] = $this->core->db('penjab')->toArray();

        $this->assign['manageURL'] = url([ADMIN, 'pendaftaran', 'manage']);
        $this->assign['form']['no_rawat'] = $this->_setNoRawat();
        //$this->assign['form']['no_rawat'] = '20200424000011';

        return $this->draw('form.html', ['pendaftaran' => $this->assign]);
    }

    public function getEdit($id)
    {
        //$id = '2020/04/24/000011';
        $id = revertNorawat($id);
        $this->_addHeaderFiles();
        $pasien = $this->db('reg_periksa')->where('no_rawat', $id)->oneArray();
        $this->assign['poliklinik'] = $this->core->db('poliklinik')->where('status', '1')->toArray();
        $this->assign['dokter'] = $this->core->db('dokter')->toArray();
        $this->assign['status_lanjut'] = $this->_addEnum('reg_periksa', 'status_lanjut');
        $this->assign['status_bayar'] = $this->_addEnum('reg_periksa', 'status_bayar');
        $this->assign['penjab'] = $this->core->db('penjab')->toArray();

        if (!empty($pasien)) {
            $this->assign['form'] = $pasien;
            $this->assign['form']['norawat'] = convertNorawat($pasien['no_rawat']);
            $this->assign['title'] = 'Edit Pendaftaran Pasien';
            $this->assign['manageURL'] = url([ADMIN, 'pendaftaran', 'manage']);
            $this->assign['pasien'] = $this->db('pasien')->where('no_rkm_medis', $pasien['no_rkm_medis'])->oneArray();

            return $this->draw('form.html', ['pendaftaran' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'pendaftaran', 'manage']));
        }
    }

    /**
    * save pasien data
    */
    public function postSave($id = null)
    {
        $errors = 0;

        $date = date('Y-m-d');
        $cek_no_rawat = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->count();

        $pasien = $this->db('pasien')->where('no_rkm_medis', $_POST['kd_poli'])->oneArray();
        $_POST['hubunganpj'] = $pasien['hubunganpj'];

        $_POST['stts'] = 'Belum';

        $_POST['stts_daftar'] = 'Baru';
        $cek_stts_daftar = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->count();
        if($cek_stts_daftar > 0) {
          $_POST['stts_daftar'] = 'Lama';
        }

        $biaya_reg = $this->db('poliklinik')->where('kd_poli', $_POST['kd_poli'])->oneArray();
        if($_POST['stts_daftar'] == 'Lama') {
          $_POST['biaya_reg'] = $biaya_reg['registrasilama'];
        }

        $_POST['status_poli'] = 'Baru';
        $cek_status_poli = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('kd_poli', $_POST['kd_poli'])->count();
        if($cek_status_poli > 0) {
          $_POST['status_poli'] = 'Lama';
        }

        // set umur
        $tanggal = new \DateTime($_POST['tgl_lahir']);
        $today = new \DateTime($date);
        $y = $today->diff($tanggal)->y;
        $m = $today->diff($tanggal)->m;
        $d = $today->diff($tanggal)->d;

        $umur="0";
        $sttsumur="Th";
        if($y>0){
            $umur=$y;
            $sttsumur="Th";
        }else if($y==0){
            if($m>0){
                $umur=$m;
                $sttsumur="Bl";
            }else if($m==0){
                $umur=$d;
                $sttsumur="Hr";
            }
        }
        $_POST['umurdaftar'] = $umur;
        $_POST['sttsumur'] = $sttsumur;

        // location to redirect
        if ($cek_no_rawat == 0) {
            $_POST['no_reg'] = $this->_setNoReg($_POST['kd_dokter']);
            $location = url([ADMIN, 'pendaftaran', 'manage']);
        } else {
            $location = url([ADMIN, 'pendaftaran', 'edit', $id]);
        }

        // check if pasien already exists
        if ($this->_pasienAlreadyExists($id)) {
            $errors++;
            $this->notify('failure', 'Pasiens sudah terdaftar ditanggal yang sama.');
        }

        // check if pasien already exists
        if(CEKSTATUSBAYAR) {
          if ($this->_cekStatusBayar($id)) {
              $errors++;
              $this->notify('failure', 'Ada tagihan belum dibayar. Silahkan hubungi kasir.');
          }
        }

        // CREATE / EDIT
        if (!$errors) {
            unset($_POST['save']);

            if ($cek_no_rawat == 0) {    // new
                $_POST['no_rawat'] = $this->_setNoRawat();
                $query = $this->db('reg_periksa')->save($_POST);
            } else {        // edit
                $dokter = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tgl_registrasi', $_POST['tgl_registrasi'])->where('kd_dokter', '<>', $_POST['kd_dokter'])->count();
                if($dokter) {
                  $_POST['no_reg'] = $this->_setNoReg($_POST['kd_dokter']);
                }
                $query = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->save($_POST);
            }


            if ($query) {
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    /**
    * remove pasien
    */
    public function getDelete($id)
    {
        if ($pendaftaran = $this->db('reg_periksa')->where('no_rawat', revertNorawat($id))->oneArray()) {
            if ($this->db('reg_periksa')->where('no_rawat', revertNorawat($id))->delete()) {
                $this->notify('success', 'Hapus sukses');
            } else {
                $this->notify('failure', 'Hapus gagal');
            }
        }
        redirect(url([ADMIN, 'pendaftaran', 'manage']));
    }

    public function getAjax()
    {
      $show = isset($_GET['show']) ? $_GET['show'] : "";
      switch($show){
       default:
         $s_keyword="";
         if (isset($_GET['keyword'])) {
             $s_keyword = $_GET['keyword'];
         }
         $search_keyword = '%'. $s_keyword .'%';

         $query = $this->db()->pdo()->prepare("SELECT * FROM pasien WHERE (no_rkm_medis LIKE ? OR nm_pasien LIKE ? OR no_ktp LIKE ? OR no_peserta LIKE ?) ORDER BY no_rkm_medis DESC LIMIT 50");
         $query->execute([$search_keyword, $search_keyword, $search_keyword, $search_keyword]);
          $rows = $query->fetchAll();
         foreach($rows as $row){
           echo '<tr class="pilihpasien" data-norkmmedis="'.$row['no_rkm_medis'].'" data-nmpasien="'.$row['nm_pasien'].'" data-namakeluarga="'.$row['namakeluarga'].'" data-alamatkeluarga="'.$row['alamatpj'].'">';
           echo '<td>'.$row['no_rkm_medis'].'</td>';
           echo '<td>'.$row['nm_pasien'].'</td>';
           echo '<td>'.$row['no_ktp'].'</td>';
           echo '<td>'.$row['namakeluarga'].'</td>';
           echo '<td>'.$row['alamatpj'].'</td>';
           echo '<td>'.$row['pekerjaan'].'</td>';
           echo '<td>'.$row['no_peserta'].'</td>';
           echo '<td>'.$row['no_tlp'].'</td>';
           echo '</tr>';
         }
        break;
      }
      exit();
    }

    public function getPrint_BuktiDaftar($id)
    {
        $pendaftaran = $this->db('reg_periksa')->where('no_rawat', revertNorawat($id))->oneArray();
        $pasien = $this->db('pasien')->where('no_rkm_medis', $pendaftaran['no_rkm_medis'])->oneArray();
        $dokter = $this->db('dokter')->where('kd_dokter', $pendaftaran['kd_dokter'])->oneArray();
        $poliklinik = $this->db('poliklinik')->where('kd_poli', $pendaftaran['kd_poli'])->oneArray();
        $penjab = $this->db('penjab')->where('kd_pj', $pendaftaran['kd_pj'])->oneArray();
        $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

        $pdf = new FPDF('P', 'mm', array(59,98));
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetTopMargin(5);
        $pdf->SetLeftMargin(5);
        $pdf->SetRightMargin(5);

        $pdf->Image($logo, 2, 2, '11', '11', 'png');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Text(15, 6, $this->core->getSettings('nama_instansi'));
        $pdf->SetFont('Arial', '', 6);
        $pdf->Text(15, 8, $this->core->getSettings('alamat_instansi'));
        $pdf->Text(15, 10, $this->core->getSettings('kontak').' - '.$this->core->getSettings('email'));
        $pdf->Text(15, 12, $this->core->getSettings('kabupaten').' - '.$this->core->getSettings('propinsi'));

      	$pdf->SetFont('Arial', '', 11);
        $pdf->Text(9, 20, 'BUKTI PENDAFTARAN');
      	$pdf->Text(5, 21, '_______________________');
      	$pdf->SetFont('Arial', '', 10);
      	$pdf->Text(15, 26, $pendaftaran['no_rawat']);
      	$pdf->SetFont('Arial', '', 9);
      	$pdf->Text(3, 35, 'Tanggal');
      	$pdf->Text(16, 35, ': '.$pendaftaran['tgl_registrasi']);
      	$pdf->Text(3, 40, 'Antrian');
        $pdf->Text(16, 40, ': '.$pendaftaran['no_reg']);
      	$pdf->Text(3, 45, 'Nama');
        $pdf->Text(16, 45, ': '.substr($pasien['nm_pasien'],0,20));
      	$pdf->Text(3, 50, 'No. RM');
        $pdf->Text(16, 50, ': '.$pendaftaran['no_rkm_medis']);
      	$pdf->Text(3, 55, 'Alamat');
      	$pdf->Text(16, 55, ': '.substr($pasien['alamat'],0,20));
      	$pdf->Text(18, 60, substr($pasien['alamat'],20,42));
      	$pdf->Text(3, 65, 'Ruang');
      	$pdf->Text(16, 65, ': '.substr($poliklinik['nm_poli'],0,20));
      	$pdf->Text(3, 70, 'Dokter');
      	$pdf->Text(16, 70, ': '.substr($dokter['nm_dokter'],0,20));
      	$pdf->Text(3, 75, 'Bayar');
      	$pdf->Text(16, 75, ': '.$penjab['png_jawab']);
      	$pdf->SetFont('Arial', '', 7);
      	$pdf->Text(9, 83, 'Terima Kasih Atas kepercayaan Anda');
      	$pdf->Text(12, 86, 'Bawalah kartu Berobat anda dan');
      	$pdf->Text(14, 89, 'datang 30 menit sebelumnya');
      	$pdf->Text(6, 92, 'Bawalah surat rujukan atau surat kontrol asli');
      	$pdf->Text(3, 95, 'dan tunjukkan pada petugas di Lobby resepsionis');

        $pdf->Output('bukti_register_'.convertNorawat($pendaftaran['no_rawat']).'.pdf','I');

    }

    /**
    * check if pasien already exists
    * @return array
    */
    private function _pasienAlreadyExists($id = null)
    {
        $date = date('Y-m-d');
        $cek_no_rawat = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->where('tgl_registrasi', $date)->count();

        if (!$cek_no_rawat) {    // new
            $count = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tgl_registrasi', $_POST['tgl_registrasi'])->count();
        } else {        // edit
            $count = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('tgl_registrasi', $_POST['tgl_registrasi'])->where('no_rawat', '<>', $_POST['no_rawat'])->count();
        }
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function _cekStatusBayar($id = null)
    {
        $date = date('Y-m-d');
        $cek_no_rawat = $this->db('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->count();

        if (!$cek_no_rawat) {    // new
          $count = $this->db('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->where('status_bayar', 'Belum Bayar')->count();
        }

        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }


    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/pendaftaran/js/admin/pendaftaran.js');
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/pendaftaran/css/admin/pendaftaran.css');
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

    private function _setNoRawat()
    {
        $date = date('Y-m-d');
        // Get last no_rawat
        $last_no_rawat = $this->db()->pdo()->prepare("SELECT ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0) FROM reg_periksa WHERE tgl_registrasi = '$date'");
        $last_no_rawat->execute();
        $last_no_rawat = $last_no_rawat->fetch();
        // Next no_rm
        if(empty($last_no_rawat[0])) {
          $last_no_rawat[0] = '000000';
        }
        $next_no_rawat = sprintf('%06s', ($last_no_rawat[0] + 1));
        $next_no_rawat = date('Y/m/d').'/'.$next_no_rawat;

        return $next_no_rawat;
    }

    private function _setNoReg($kd_dokter)
    {
        $date = date('Y-m-d');
        // Get last no_rawat
        $last_no_reg = $this->db()->pdo()->prepare("SELECT MAX(no_reg) FROM reg_periksa WHERE tgl_registrasi = '$date' AND kd_dokter = '$kd_dokter'");
        $last_no_reg->execute();
        $last_no_reg = $last_no_reg->fetch();
        // Next no_rm
        if(empty($last_no_reg[0])) {
          $last_no_reg[0] = '000';
        }
        $next_no_reg = sprintf('%03s', ($last_no_reg[0] + 1));

        return $next_no_reg;
    }

    private function _setUmur($tanggal)
    {
        list($cY, $cm, $cd) = explode('-', date('Y-m-d'));
        list($Y, $m, $d) = explode('-', date('Y-m-d', strtotime($tanggal)));
        $umur = $cY - $Y;
        return $umur;
    }

    private function _addEnum($table_name, $column_name) {
      $result = $this->db()->pdo()->prepare("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
      $result->execute();
      $result = $result->fetch();
      $result = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2", $result[1]));
      return $result;
    }

}
