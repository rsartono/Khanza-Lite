<?php

namespace Plugins\Farmasi;

use Systems\AdminModule;
use Systems\Lib\Fpdf\PDF_MC_Table;

class Admin extends AdminModule
{

    public function navigation()
    {
        return [
            'Manage' => 'manage',
            'Master' => 'master',
        ];
    }

    /* Databarang Section */
    public function getManage($page = 1)
    {
        $this->_addHeaderFiles();
        $perpage = '10';
        $phrase = '';
        if(isset($_GET['s']))
          $phrase = $_GET['s'];

        $status = '1';
        if(isset($_GET['status']))
          $status = $_GET['status'];

        // pagination
        $totalRecords = $this->db('databarang')
            ->where('status', $status)
            ->like('kode_brng', '%'.$phrase.'%')
            ->like('nama_brng', '%'.$phrase.'%')
            ->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'farmasi', 'manage', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $rows = $this->db('databarang')
            ->where('status', $status)
            ->like('kode_brng', '%'.$phrase.'%')
            ->like('nama_brng', '%'.$phrase.'%')
            ->offset($offset)
            ->limit($perpage)
            ->toArray();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'farmasi', 'edit', $row['kode_brng']]);
                $row['delURL']  = url([ADMIN, 'farmasi', 'delete', $row['kode_brng']]);
                $row['restoreURL']  = url([ADMIN, 'farmasi', 'restore', $row['kode_brng']]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['title'] = 'Kelola Databarang';
        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['addURL'] = url([ADMIN, 'farmasi', 'add']);
        $this->assign['printURL'] = url([ADMIN, 'farmasi', 'print']);

        return $this->draw('manage.html', ['databarang' => $this->assign]);

    }

    public function getAdd()
    {
        $this->_addHeaderFiles();
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kode_brng' => '',
              'nama_brng' => '',
              'kode_satbesar' => '',
              'kode_sat' => '',
              'letak_barang' => '',
              'dasar' => '',
              'h_beli' => '',
              'ralan' => '',
              'kelas1' => '',
              'kelas2' => '',
              'kelas3' => '',
              'utama' => '',
              'vip' => '',
              'vvip' => '',
              'beliluar' => '',
              'jualbebas' => '',
              'karyawan' => '',
              'stokminimal' => '',
              'kdjns' => '',
              'isi' => '',
              'kapasitas' => '',
              'expire' => '',
              'status' => '',
              'kode_industri' => '',
              'kode_kategori' => '',
              'kode_golongan' => ''
            ];
        }

        $this->assign['title'] = 'Tambah Databarang';
        $this->assign['status'] = $this->core->getEnum('databarang', 'status');
        $this->assign['kdjns'] = $this->db('jenis')->toArray();
        $this->assign['kode_sat'] = $this->db('kodesatuan')->toArray();
        $this->assign['kode_industri'] = $this->db('industrifarmasi')->toArray();
        $this->assign['kode_kategori'] = $this->db('kategori_barang')->toArray();
        $this->assign['kode_golongan'] = $this->db('golongan_barang')->toArray();

        return $this->draw('form.html', ['databarang' => $this->assign]);
    }

    public function getEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('databarang')->where('kode_brng', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Databarang';
            $this->assign['status'] = $this->core->getEnum('databarang', 'status');
            $this->assign['kdjns'] = $this->db('jenis')->toArray();
            $this->assign['kode_sat'] = $this->db('kodesatuan')->toArray();
            $this->assign['kode_industri'] = $this->db('industrifarmasi')->toArray();
            $this->assign['kode_kategori'] = $this->db('kategori_barang')->toArray();
            $this->assign['kode_golongan'] = $this->db('golongan_barang')->toArray();

            return $this->draw('form.html', ['databarang' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'farmasi', 'manage']));
        }
    }

    public function getDelete($id)
    {
        if ($this->core->db('databarang')->where('kode_brng', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'farmasi', 'manage']));
    }

    public function getRestore($id)
    {
        if ($this->core->db('databarang')->where('kode_brng', $id)->update('status', '1')) {
            $this->notify('success', 'Restore sukses');
        } else {
            $this->notify('failure', 'Restore gagal');
        }
        redirect(url([ADMIN, 'farmasi', 'manage']));
    }

    public function postSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'farmasi', 'add']);
        } else {
            $location = url([ADMIN, 'farmasi', 'edit', $id]);
        }

        if (checkEmptyFields(['kode_brng', 'nama_brng'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = '1';
                $query = $this->db('databarang')->save($_POST);
            } else {        // edit
                $query = $this->db('databarang')->where('kode_brng', $id)->save($_POST);
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

    public function getPrint()
    {
      $pasien = $this->db('databarang')->toArray();
      $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

      $pdf = new PDF_MC_Table('L','mm','Legal');
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(true, 10);
      $pdf->SetTopMargin(10);
      $pdf->SetLeftMargin(10);
      $pdf->SetRightMargin(10);

      $pdf->Image($logo, 10, 8, '18', '18', 'png');
      $pdf->SetFont('Arial', '', 24);
      $pdf->Text(30, 16, $this->core->getSettings('nama_instansi'));
      $pdf->SetFont('Arial', '', 10);
      $pdf->Text(30, 21, $this->core->getSettings('alamat_instansi').' - '.$this->core->getSettings('kabupaten'));
      $pdf->Text(30, 25, $this->core->getSettings('kontak').' - '.$this->core->getSettings('email'));
      $pdf->Line(10, 30, 345, 30);
      $pdf->Line(10, 31, 345, 31);
      $pdf->Text(10, 40, 'DATA DATABARANG');
      $pdf->Ln(34);
      $pdf->SetFont('Arial', '', 10);
      $pdf->SetWidths(array(25,50,20,20,20,20,20,20,20,20,20,20,20,20,20,20));
      $pdf->Row(array('Kode Barang', 'Nama Barang', 'H. Dasar', 'H. Beli', 'Ralan', 'Kelas 1', 'Kelas 2', 'Kelas 3', 'Utama', 'VIP', 'VVIP', 'Beli Luar', 'Jual Bebas', 'Karyawan', 'Status'));

      foreach ($pasien as $hasil) {
        $status = 'Aktif';
        if($hasil['status'] == '0') {
          $status = 'Tidak Aktif';
        }
        $pdf->Row(array(
          $hasil['kode_brng'],
          $hasil['nama_brng'],
          number_format($hasil['dasar'],0,",","."),
          number_format($hasil['h_beli'],0,",","."),
          number_format($hasil['ralan'],0,",","."),
          number_format($hasil['kelas1'],0,",","."),
          number_format($hasil['kelas2'],0,",","."),
          number_format($hasil['kelas3'],0,",","."),
          number_format($hasil['utama'],0,",","."),
          number_format($hasil['vip'],0,",","."),
          number_format($hasil['vvip'],0,",","."),
          number_format($hasil['beliluar'],0,",","."),
          number_format($hasil['jualbebas'],0,",","."),
          number_format($hasil['karyawan'],0,",","."),
          $status
        ));
      }
      $pdf->Output('laporan_pasien_'.date('Y-m-d').'.pdf','I');

    }

    /* End Databarang Section */

    /* Master Farmasi Section */
    public function getMaster()
    {
    }

    public function getCSS()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/farmasi/css/admin/farmasi.css');
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/farmasi/js/admin/farmasi.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'farmasi', 'css']));
        $this->core->addJS(url([ADMIN, 'farmasi', 'javascript']), 'footer');
    }

}
