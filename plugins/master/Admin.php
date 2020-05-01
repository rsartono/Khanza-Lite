<?php

namespace Plugins\Master;

use Systems\AdminModule;
use Systems\Lib\Fpdf\PDF_MC_Table;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Dokter' => 'dokter',
            'Petugas' => 'petugas',
            'Poliklinik' => 'poliklinik',
            'Data Barang' => 'databarang',
            'Perawatan Ralan' => 'jnsperawatan',
            'Perawatan Ranap' => 'jnsperawatan_ranap',
            'Perawatan Laboratorium' => 'jnsperawatan_lab',
            'Perawatan Radiologi' => 'jnsperawatan_rad',
        ];
    }

    /* Master Dokter Section */
    public function getDokter($page = 1)
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
        $totalRecords = $this->db()->pdo()->prepare("SELECT * FROM dokter WHERE (kd_dokter LIKE ? OR nm_dokter LIKE ?) AND status = '$status'");
        $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $totalRecords = $totalRecords->fetchAll();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'dokter', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM dokter WHERE (kd_dokter LIKE ? OR nm_dokter LIKE ?) AND status = '$status' LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'dokteredit', $row['kd_dokter']]);
                $row['delURL']  = url([ADMIN, 'master', 'dokterdelete', $row['kd_dokter']]);
                $row['restoreURL']  = url([ADMIN, 'master', 'dokterrestore', $row['kd_dokter']]);
                $row['viewURL'] = url([ADMIN, 'master', 'dokterview', $row['kd_dokter']]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['addURL'] = url([ADMIN, 'master', 'dokteradd']);
        $this->assign['printURL'] = url([ADMIN, 'master', 'dokterprint']);

        return $this->draw('dokter.manage.html', ['dokter' => $this->assign]);

    }

    public function getDokterAdd()
    {
        $this->_addHeaderFiles();
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kd_dokter' => '',
              'nm_dokter' => '',
              'jk' => '',
              'tmp_lahir' => '',
              'tgl_lahir' => '',
              'gol_drh' => '',
              'agama' => '',
              'almt_tgl' => '',
              'no_telp' => '',
              'stts_nikah' => '',
              'kd_sps' => '',
              'alumni' => '',
              'no_ijn_praktek' => '',
              'status' => ''
            ];
        }

        $this->assign['title'] = 'Tambah Dokter';
        $this->assign['kd_dokter'] = $this->db('pegawai')->toArray();
        $this->assign['jk'] = $this->_addEnum('dokter', 'jk');
        $this->assign['gol_drh'] = $this->_addEnum('dokter', 'gol_drh');
        $this->assign['agama'] = array('ISLAM', 'KRISTEN', 'PROTESTAN', 'HINDU', 'BUDHA', 'KONGHUCU', 'KEPERCAYAAN');
        $this->assign['stts_nikah'] = $this->_addEnum('dokter', 'stts_nikah');
        $this->assign['kd_sps'] = $this->db('spesialis')->toArray();

        return $this->draw('dokter.form.html', ['dokter' => $this->assign]);
    }

    public function getDokterEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('dokter')->where('kd_dokter', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Dokter';

            return $this->draw('dokter.form.html', ['dokter' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'master', 'dokter']));
        }
    }

    public function getDokterDelete($id)
    {
        if ($this->core->db('dokter')->where('kd_dokter', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'master', 'dokter']));
    }

    public function getDokterRestore($id)
    {
        if ($this->core->db('dokter')->where('kd_dokter', $id)->update('status', '1')) {
            $this->notify('success', 'Restore sukses');
        } else {
            $this->notify('failure', 'Restore gagal');
        }
        redirect(url([ADMIN, 'master', 'dokter']));
    }

    public function postDokterSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'master', 'dokteradd']);
        } else {
            $location = url([ADMIN, 'master', 'dokteredit', $id]);
        }

        /*if (checkEmptyFields(['kd_dokter', 'nm_dokter'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }*/

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = '1';
                $query = $this->db('dokter')->save($_POST);
            } else {        // edit
                $query = $this->db('dokter')->where('kd_dokter', $id)->save($_POST);
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
    /* End Master Dokter Section */

    /* Master Petugas Section */
    public function getPetugas($page = 1)
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
        $totalRecords = $this->db()->pdo()->prepare("SELECT * FROM petugas WHERE (nip LIKE ? OR nama LIKE ?) AND status = '$status'");
        $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $totalRecords = $totalRecords->fetchAll();

        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'petugas', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM petugas WHERE (nip LIKE ? OR nama LIKE ?) AND status = '$status' LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'petugasedit', $row['nip']]);
                $row['delURL']  = url([ADMIN, 'master', 'petugasdelete', $row['nip']]);
                $row['restoreURL']  = url([ADMIN, 'master', 'petugasrestore', $row['nip']]);
                $row['viewURL'] = url([ADMIN, 'master', 'petugasview', $row['nip']]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['addURL'] = url([ADMIN, 'master', 'petugasadd']);
        $this->assign['printURL'] = url([ADMIN, 'master', 'petugasprint']);

        return $this->draw('petugas.manage.html', ['petugas' => $this->assign]);

    }

    public function getPetugasAdd()
    {
        $this->_addHeaderFiles();
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'nip' => '',
              'nama' => '',
              'jk' => '',
              'tmp_lahir' => '',
              'tgl_lahir' => '',
              'gol_darah' => '',
              'agama' => '',
              'alamat' => '',
              'no_telp' => '',
              'stts_nikah' => '',
              'kd_jbtn' => '',
              'status' => ''
            ];
        }

        $this->assign['title'] = 'Tambah Petugas';
        $this->assign['nip'] = $this->db('pegawai')->toArray();
        $this->assign['jk'] = $this->_addEnum('petugas', 'jk');
        $this->assign['gol_darah'] = $this->_addEnum('petugas', 'gol_darah');
        $this->assign['agama'] = array('ISLAM', 'KRISTEN', 'PROTESTAN', 'HINDU', 'BUDHA', 'KONGHUCU', 'KEPERCAYAAN');
        $this->assign['stts_nikah'] = $this->_addEnum('petugas', 'stts_nikah');
        $this->assign['kd_jbtn'] = $this->db('jabatan')->toArray();

        return $this->draw('petugas.form.html', ['petugas' => $this->assign]);
    }

    public function getPetugasEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('petugas')->where('nip', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Petugas';

            return $this->draw('petugas.form.html', ['petugas' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'master', 'petugas']));
        }
    }

    public function getPetugasDelete($id)
    {
        if ($this->core->db('petugas')->where('nip', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'master', 'petugas']));
    }

    public function getPetugasRestore($id)
    {
        if ($this->core->db('petugas')->where('nip', $id)->update('status', '1')) {
            $this->notify('success', 'Restore sukses');
        } else {
            $this->notify('failure', 'Restore gagal');
        }
        redirect(url([ADMIN, 'master', 'petugas']));
    }

    public function postPetugasSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'master', 'petugasadd']);
        } else {
            $location = url([ADMIN, 'master', 'petugasedit', $id]);
        }

        /*if (checkEmptyFields(['nip', 'nama'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }*/

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = '1';
                $query = $this->db('petugas')->save($_POST);
            } else {        // edit
                $query = $this->db('petugas')->where('nip', $id)->save($_POST);
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
    /* End Master Petugas Section */

    /* Master Poliklinik Section */
    public function getPoliklinik($page = 1)
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
        $totalRecords = $this->db()->pdo()->prepare("SELECT * FROM poliklinik WHERE (kd_poli LIKE ? OR nm_poli LIKE ?) AND status = '$status'");
        $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $totalRecords = $totalRecords->fetchAll();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'poliklinik', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM poliklinik WHERE (kd_poli LIKE ? OR nm_poli LIKE ?) AND status = '$status' LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'poliklinikedit', $row['kd_poli']]);
                $row['delURL']  = url([ADMIN, 'master', 'poliklinikdelete', $row['kd_poli']]);
                $row['restoreURL']  = url([ADMIN, 'master', 'poliklinikrestore', $row['kd_poli']]);
                $row['viewURL'] = url([ADMIN, 'master', 'poliklinikview', $row['kd_poli']]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['addURL'] = url([ADMIN, 'master', 'poliklinikadd']);
        $this->assign['printURL'] = url([ADMIN, 'master', 'poliklinikprint']);

        return $this->draw('poliklinik.manage.html', ['poliklinik' => $this->assign]);

    }

    public function getPoliklinikAdd()
    {
        $this->_addHeaderFiles();
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kd_poli' => '',
              'nm_poli' => '',
              'registrasi' => '',
              'registrasilama' => '',
              'status' => ''
            ];
        }

        $this->assign['title'] = 'Tambah Poliklinik';

        return $this->draw('poliklinik.form.html', ['poliklinik' => $this->assign]);
    }

    public function getPoliklinikEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('poliklinik')->where('kd_poli', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Poliklinik';

            return $this->draw('poliklinik.form.html', ['poliklinik' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'master', 'poliklinik']));
        }
    }

    public function getPoliklinikDelete($id)
    {
        if ($this->core->db('poliklinik')->where('kd_poli', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'master', 'poliklinik']));
    }

    public function getPoliklinikRestore($id)
    {
        if ($this->core->db('poliklinik')->where('kd_poli', $id)->update('status', '1')) {
            $this->notify('success', 'Restore sukses');
        } else {
            $this->notify('failure', 'Restore gagal');
        }
        redirect(url([ADMIN, 'master', 'poliklinik']));
    }

    public function postPoliklinikSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'master', 'poliklinikadd']);
        } else {
            $location = url([ADMIN, 'master', 'poliklinikedit', $id]);
        }

        if (checkEmptyFields(['kd_poli', 'nm_poli'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = '1';
                $query = $this->db('poliklinik')->save($_POST);
            } else {        // edit
                $query = $this->db('poliklinik')->where('kd_poli', $id)->save($_POST);
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

    public function getPoliklinikPrint()
    {
      $pasien = $this->db('poliklinik')->toArray();
      $logo = 'data:image/png;base64,' . base64_encode($this->core->getSettings('logo'));

      $pdf = new PDF_MC_Table();
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
      $pdf->Line(10, 30, 200, 30);
      $pdf->Line(10, 31, 200, 31);
      $pdf->Text(10, 40, 'DATA POLIKLINIK');
      $pdf->Ln(34);
      $pdf->SetFont('Arial', '', 10);
      $pdf->SetWidths(array(20,80,25,25,40));
      $pdf->Row(array('Kode Poli','Nama Poli','Daftar Baru', 'Daftar Lama', 'Status'));
      foreach ($pasien as $hasil) {
        $status = 'Aktif';
        if($hasil['status'] == '0') {
          $status = 'Tidak Aktif';
        }
        $pdf->Row(array($hasil['kd_poli'],$hasil['nm_poli'],$hasil['registrasi'],$hasil['registrasilama'],$status));
      }
      $pdf->Output('laporan_pasien_'.date('Y-m-d').'.pdf','I');

    }
    /* End Master Poliklinik Section */

    /* Master Databarang Section */
    public function getDatabarang($page = 1)
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
        $totalRecords = $this->db()->pdo()->prepare("SELECT * FROM databarang WHERE (kode_brng LIKE ? OR nama_brng LIKE ?) AND status = '$status'");
        $totalRecords->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $totalRecords = $totalRecords->fetchAll();
        //$totalRecords = $this->core->db('databarang')->like('kode_brng', '%'.$phrase.'%')->orLike('nama_brng', '%'.$phrase.'%')->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'databarang', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM databarang WHERE (kode_brng LIKE ? OR nama_brng LIKE ?) AND status = '$status' LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'databarangedit', $row['kode_brng']]);
                $row['delURL']  = url([ADMIN, 'master', 'databarangdelete', $row['kode_brng']]);
                $row['restoreURL']  = url([ADMIN, 'master', 'databarangrestore', $row['kode_brng']]);
                $row['viewURL'] = url([ADMIN, 'master', 'databarangview', $row['kode_brng']]);
                $this->assign['list'][] = $row;
            }
        }

        $this->assign['title'] = 'Kelola Databarang';
        $this->assign['getStatus'] = isset($_GET['status']);
        $this->assign['addURL'] = url([ADMIN, 'master', 'databarangadd']);
        $this->assign['printURL'] = url([ADMIN, 'master', 'databarangprint']);

        return $this->draw('databarang.manage.html', ['databarang' => $this->assign]);

    }

    public function getDatabarangAdd()
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
        $this->assign['status'] = $this->_addEnum('databarang', 'status');
        $this->assign['kdjns'] = $this->db('jenis')->toArray();
        $this->assign['kode_sat'] = $this->db('kodesatuan')->toArray();
        $this->assign['kode_industri'] = $this->db('industrifarmasi')->toArray();
        $this->assign['kode_kategori'] = $this->db('kategori_barang')->toArray();
        $this->assign['kode_golongan'] = $this->db('golongan_barang')->toArray();

        return $this->draw('databarang.form.html', ['databarang' => $this->assign]);
    }

    public function getDatabarangEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('databarang')->where('kode_brng', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Databarang';
            $this->assign['status'] = $this->_addEnum('databarang', 'status');
            $this->assign['kdjns'] = $this->db('jenis')->toArray();
            $this->assign['kode_sat'] = $this->db('kodesatuan')->toArray();
            $this->assign['kode_industri'] = $this->db('industrifarmasi')->toArray();
            $this->assign['kode_kategori'] = $this->db('kategori_barang')->toArray();
            $this->assign['kode_golongan'] = $this->db('golongan_barang')->toArray();

            return $this->draw('databarang.form.html', ['databarang' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'master', 'databarang']));
        }
    }

    public function getDatabarangDelete($id)
    {
        if ($this->core->db('databarang')->where('kode_brng', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'master', 'databarang']));
    }

    public function postDatabarangSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'master', 'databarangadd']);
        } else {
            $location = url([ADMIN, 'master', 'databarangedit', $id]);
        }

        if (checkEmptyFields(['kode_brng', 'nama_brng'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = 1;
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
    /* End Master Databarang Section */

    /* Master Jns_Perawatan Section */
    public function getJnsPerawatan($page = 1)
    {
        $this->_addHeaderFiles();
        $perpage = '10';
        $phrase = '';
        if(isset($_GET['s']))
          $phrase = $_GET['s'];

        // pagination
        $totalRecords = $this->core->db('jns_perawatan')->like('kd_jenis_prw', '%'.$phrase.'%')->orLike('nm_perawatan', '%'.$phrase.'%')->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'jnsperawatan', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM jns_perawatan WHERE (kd_jenis_prw LIKE ? OR nm_perawatan LIKE ?) LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'jnsperawatanedit', $row['kd_jenis_prw']]);
                $row['delURL']  = url([ADMIN, 'master', 'jnsperawatandelete', $row['kd_jenis_prw']]);
                $row['viewURL'] = url([ADMIN, 'master', 'jnsperawatanview', $row['kd_jenis_prw']]);
                $this->assign['list'][] = $row;
            }
        }

        return $this->draw('jnsperawatan.manage.html', ['jnsperawatan' => $this->assign]);

    }

    public function getJnsPerawatanAdd()
    {
        $this->_addHeaderFiles();
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = ['kd_poli' => '', 'nm_poli' => '', 'registrasi' => '', 'registrasilama' => '', 'status' => ''];
        }

        $this->assign['title'] = 'Tambah Poliklinik';

        return $this->draw('jnsperawatan.form.html', ['poliklinik' => $this->assign]);
    }

    public function getJnsPerawatanEdit($id)
    {
        $this->_addHeaderFiles();
        $row = $this->db('poliklinik')->where('kd_poli', $id)->oneArray();
        if (!empty($row)) {
            $this->assign['form'] = $row;
            $this->assign['title'] = 'Edit Poliklinik';

            return $this->draw('jnsperawatan.form.html', ['poliklinik' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'master', 'poliklinik']));
        }
    }

    public function getJnsPerawatanDelete($id)
    {
        if ($this->core->db('poliklinik')->where('kd_poli', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'master', 'jnsperawatan']));
    }

    public function postJnsPerawatanSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'master', 'poliklinikadd']);
        } else {
            $location = url([ADMIN, 'master', 'poliklinikedit', $id]);
        }

        if (checkEmptyFields(['kd_poli', 'nm_poli'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = 1;
                $query = $this->db('poliklinik')->save($_POST);
            } else {        // edit
                $query = $this->db('poliklinik')->where('kd_poli', $id)->save($_POST);
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
    /* End Master Jns_Perawatan Section */

    public function getCSS()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/master/css/admin/master.css');
        exit();
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/master/js/admin/master.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'master', 'css']));
        $this->core->addJS(url([ADMIN, 'master', 'javascript']), 'footer');
    }

    private function _addEnum($table_name, $column_name) {
      $result = $this->db()->pdo()->prepare("SHOW COLUMNS FROM $table_name LIKE '$column_name'");
      $result->execute();
      $result = $result->fetch();
      $result = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2", $result[1]));
      return $result;
    }

}
