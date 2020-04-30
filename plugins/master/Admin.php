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
            'Jenis Perawatan' => 'jnsperawatan',
        ];
    }

    /* Master Dokter Section */
    public function getDokter($page = 1)
    {
        $perpage = '10';
        $phrase = '';
        if(isset($_GET['s']))
          $phrase = $_GET['s'];

        // pagination
        $totalRecords = $this->core->db('dokter')->like('kd_dokter', '%'.$phrase.'%')->orLike('nm_dokter', '%'.$phrase.'%')->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'dokter', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM dokter WHERE (kd_dokter LIKE ? OR nm_dokter LIKE ?) LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'dokteredit', $row['kd_dokter']]);
                $row['delURL']  = url([ADMIN, 'master', 'dokterdelete', $row['kd_dokter']]);
                $row['viewURL'] = url([ADMIN, 'master', 'dokterview', $row['kd_dokter']]);
                $this->assign['list'][] = $row;
            }
        }

        return $this->draw('dokter.manage.html', ['dokter' => $this->assign]);

    }

    public function getDokterAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = [
              'kd_dokter' => '',
              'nm_dokter' => '',
              'registrasi' => '',
              'registrasilama' => '',
              'status' => ''
            ];
        }

        $this->assign['title'] = 'Tambah Dokter';

        return $this->draw('dokter.form.html', ['dokter' => $this->assign]);
    }

    public function getDokterEdit($id)
    {
        $user = $this->db('dokter')->where('kd_dokter', $id)->oneArray();
        if (!empty($user)) {
            $this->assign['form'] = $user;
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

    public function postDokterSave($id = null)
    {
        $errors = 0;

        if (!$id) {
            $location = url([ADMIN, 'master', 'dokteradd']);
        } else {
            $location = url([ADMIN, 'master', 'dokteredit', $id]);
        }

        if (checkEmptyFields(['kd_dokter', 'nm_dokter'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $_POST['status'] = 1;
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

    /* Master Poliklinik Section */
    public function getPoliklinik($page = 1)
    {
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
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = ['kd_poli' => '', 'nm_poli' => '', 'registrasi' => '', 'registrasilama' => '', 'status' => ''];
        }

        $this->assign['title'] = 'Tambah Poliklinik';

        return $this->draw('poliklinik.form.html', ['poliklinik' => $this->assign]);
    }

    public function getPoliklinikEdit($id)
    {
        $user = $this->db('poliklinik')->where('kd_poli', $id)->oneArray();
        if (!empty($user)) {
            $this->assign['form'] = $user;
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
        $perpage = '10';
        $phrase = '';
        if(isset($_GET['s']))
          $phrase = $_GET['s'];

        // pagination
        $totalRecords = $this->core->db('databarang')->like('kode_brng', '%'.$phrase.'%')->orLike('nama_brng', '%'.$phrase.'%')->toArray();
        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'master', 'databarang', '%d']));
        $this->assign['pagination'] = $pagination->nav('pagination','5');
        $this->assign['totalRecords'] = $totalRecords;

        // list
        $offset = $pagination->offset();
        $query = $this->db()->pdo()->prepare("SELECT * FROM databarang WHERE (kode_brng LIKE ? OR nama_brng LIKE ?) LIMIT $perpage OFFSET $offset");
        $query->execute(['%'.$phrase.'%', '%'.$phrase.'%']);
        $rows = $query->fetchAll();

        $this->assign['list'] = [];
        if (count($rows)) {
            foreach ($rows as $row) {
                $row = htmlspecialchars_array($row);
                $row['editURL'] = url([ADMIN, 'master', 'databarangedit', $row['kode_brng']]);
                $row['delURL']  = url([ADMIN, 'master', 'databarangdelete', $row['kode_brng']]);
                $row['viewURL'] = url([ADMIN, 'master', 'databarangview', $row['kode_brng']]);
                $this->assign['list'][] = $row;
            }
        }

        return $this->draw('databarang.manage.html', ['databarang' => $this->assign]);

    }

    public function getDatabarangAdd()
    {
        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = ['kd_poli' => '', 'nm_poli' => '', 'registrasi' => '', 'registrasilama' => '', 'status' => ''];
        }

        $this->assign['title'] = 'Tambah Poliklinik';

        return $this->draw('poliklinik.form.html', ['poliklinik' => $this->assign]);
    }

    public function getDatabarangEdit($id)
    {
        $user = $this->db('poliklinik')->where('kd_poli', $id)->oneArray();
        if (!empty($user)) {
            $this->assign['form'] = $user;
            $this->assign['title'] = 'Edit Poliklinik';

            return $this->draw('poliklinik.form.html', ['poliklinik' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'master', 'poliklinik']));
        }
    }

    public function getDatabarangDelete($id)
    {
        if ($this->core->db('poliklinik')->where('kd_poli', $id)->update('status', '0')) {
            $this->notify('success', 'Hapus sukses');
        } else {
            $this->notify('failure', 'Hapus gagal');
        }
        redirect(url([ADMIN, 'master', 'poliklinik']));
    }

    public function postDatabarangSave($id = null)
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
    /* End Master Databarang Section */

    /* Master Jns_Perawatan Section */
    public function getJnsPerawatan($page = 1)
    {
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
        $user = $this->db('poliklinik')->where('kd_poli', $id)->oneArray();
        if (!empty($user)) {
            $this->assign['form'] = $user;
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
