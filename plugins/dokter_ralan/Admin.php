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

    public function getView($id, $page = 1)
    {
        $id = revertNorawat($id);
        $this->_addHeaderFiles();
        $reg_periksa = $this->db('reg_periksa')->where('no_rawat', $id)->oneArray();
        $pasien = $this->db('pasien')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->oneArray();
        $count_ralan = $this->db('reg_periksa')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('status_lanjut', 'Ralan')->count();
        $count_ranap = $this->db('reg_periksa')->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])->where('status_lanjut', 'Ranap')->count();
        $this->assign['print_rm'] = url([ADMIN, 'dokter_ralan', 'print_rm', $reg_periksa['no_rkm_medis']]);

        if (!empty($reg_periksa)) {
	        $perpage = '5';
            $this->assign['no_rawat'] = convertNorawat($id);
            $this->assign['view'] = $reg_periksa;
            $this->assign['view']['pasien'] = $pasien;
            $this->assign['view']['count_ralan'] = $count_ralan;
            $this->assign['view']['count_ranap'] = $count_ranap;
            $this->assign['soap'] = $this->db('pemeriksaan_ralan')->where('no_rawat', $id)->oneArray();
            $this->assign['metode_racik'] = $this->core->db('metode_racik')->toArray();
            $this->assign['diagnosa_pasien'] = $this->db('diagnosa_pasien')->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')->where('no_rawat', $id)->toArray();
            $this->assign['prosedur_pasien'] = $this->db('prosedur_pasien')->join('icd9', 'icd9.kode = prosedur_pasien.kode')->where('no_rawat', $id)->toArray();
            $this->assign['rawat_jl_dr'] = $this->db('rawat_jl_dr')->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')->where('no_rawat', $id)->toArray();
            $this->assign['catatan'] = $this->db('catatan_perawatan')->where('no_rawat', $id)->oneArray();
            $this->assign['permintaan_resep'] = $this->db('resep_obat')->join('resep_dokter', 'resep_dokter.no_resep = resep_obat.no_resep')->join('databarang', 'databarang.kode_brng = resep_dokter.kode_brng')->where('no_rawat', $id)->toArray();
            $this->assign['permintaan_resep_racikan'] = $this->db('resep_obat')
                ->join('resep_dokter_racikan', 'resep_dokter_racikan.no_resep = resep_obat.no_resep')
                ->join('resep_dokter_racikan_detail', 'resep_dokter_racikan_detail.no_racik = resep_dokter_racikan.no_racik')
                ->join('databarang', 'databarang.kode_brng = resep_dokter_racikan_detail.kode_brng')
                ->where('no_rawat', $id)
                ->group('resep_dokter_racikan.no_racik')
                ->toArray();
            $this->assign['permintaan_lab'] = $this->db('permintaan_lab')
                ->join('permintaan_pemeriksaan_lab', 'permintaan_pemeriksaan_lab.noorder = permintaan_lab.noorder')
                ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw = permintaan_pemeriksaan_lab.kd_jenis_prw')
                ->where('no_rawat', $id)
                ->toArray();
            $this->assign['permintaan_rad'] = $this->db('permintaan_radiologi')
                ->join('permintaan_pemeriksaan_radiologi', 'permintaan_pemeriksaan_radiologi.noorder = permintaan_radiologi.noorder')
                ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw = permintaan_pemeriksaan_radiologi.kd_jenis_prw')
                ->where('no_rawat', $id)
                ->toArray();
            $this->assign['fotoURL'] = url(MODULES.'/dokter_ralan/img/'.$pasien['jk'].'.png');
            $this->assign['manageURL'] = url([ADMIN, 'dokter_ralan', 'manage']);
            $totalRecords = $this->db('reg_periksa')
                ->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])
                ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
                ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                ->desc('tgl_registrasi')
                ->toArray();
  	        $pagination = new \Systems\Lib\Pagination($page, count($totalRecords), 10, url([ADMIN, 'dokter_ralan', 'view', convertNorawat($id), '%d']));
  	        $this->assign['pagination'] = $pagination->nav('pagination','5');
  	        $offset = $pagination->offset();
            $rows = $this->db('reg_periksa')
                ->where('no_rkm_medis', $reg_periksa['no_rkm_medis'])
                ->join('poliklinik', 'poliklinik.kd_poli = reg_periksa.kd_poli')
                ->join('dokter', 'dokter.kd_dokter = reg_periksa.kd_dokter')
                ->offset($offset)
                ->limit($perpage)
                ->desc('tgl_registrasi')
                ->toArray();

            foreach ($rows as &$row) {
                $pemeriksaan_ralan = $this->db('pemeriksaan_ralan')->where('no_rawat', $row['no_rawat'])->oneArray();
                $diagnosa_pasien = $this->db('diagnosa_pasien')->join('penyakit', 'penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit')->where('no_rawat', $row['no_rawat'])->toArray();
                $prosedur_pasien = $this->db('prosedur_pasien')->join('icd9', 'icd9.kode = prosedur_pasien.kode')->where('no_rawat', $row['no_rawat'])->toArray();
                $rawat_jl_dr = $this->db('rawat_jl_dr')->join('jns_perawatan', 'jns_perawatan.kd_jenis_prw = rawat_jl_dr.kd_jenis_prw')->where('no_rawat', $row['no_rawat'])->toArray();
                $detail_pemberian_obat = $this->db('detail_pemberian_obat')->join('databarang', 'databarang.kode_brng = detail_pemberian_obat.kode_brng')->where('no_rawat', $row['no_rawat'])->toArray();
                $detail_periksa_lab = $this->db('detail_periksa_lab')->join('template_laboratorium', 'template_laboratorium.id_template = detail_periksa_lab.id_template')->where('no_rawat', $row['no_rawat'])->toArray();
                $hasil_radiologi = $this->db('hasil_radiologi')->where('no_rawat', $row['no_rawat'])->oneArray();
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
                $row['prosedur_pasien'] = $prosedur_pasien;
                $row['rawat_jl_dr'] = $rawat_jl_dr;
                $row['detail_pemberian_obat'] = $detail_pemberian_obat;
                $row['detail_periksa_lab'] = $detail_periksa_lab;
                $this->assign['riwayat'][] = $row;
            }

            return $this->draw('view.html', ['dokter_ralan' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'dokter_ralan', 'manage']));
        }
    }

    public function postSOAPSave($id = null)
    {
        $errors = 0;
        $location = url([ADMIN, 'dokter_ralan', 'view', $id]);

        if (!$errors) {
            unset($_POST['save']);

            $query = $this->db('pemeriksaan_ralan')
              ->save([
                'no_rawat' => revertNorawat($id),
                'tgl_perawatan' => date('Y-m-d'),
                'jam_rawat' => date('H:i:s'),
                'suhu_tubuh' => $_POST['suhu_tubuh'],
                'tensi' => $_POST['tensi'],
                'nadi' => $_POST['nadi'],
                'respirasi' => $_POST['respirasi'],
                'tinggi' => $_POST['tinggi'],
                'berat' => $_POST['berat'],
                'gcs' => $_POST['gcs'],
                'keluhan' => $_POST['keluhan'],
                'pemeriksaan' => $_POST['pemeriksaan'],
                'alergi' => '-',
                'imun_ke' => '1',
                'rtl' => $_POST['rtl'],
                'penilaian' => 'penilaiannya'
              ]);

            $get_kd_penyakit = $_POST['kd_penyakit'];
            for ($i = 0; $i < count($get_kd_penyakit); $i++) {
              $kd_penyakit = $get_kd_penyakit[$i];
              $query = $this->db('diagnosa_pasien')
                ->save([
                  'no_rawat' => revertNorawat($id),
                  'kd_penyakit' => $kd_penyakit,
                  'status' => 'Ralan',
                  'prioritas' => $i+1,
                  'status_penyakit' => 'Lama'
                ]);
            }

            $get_kode = $_POST['kode'];
            for ($i = 0; $i < count($get_kode); $i++) {
              $kode = $get_kode[$i];
              $query = $this->db('prosedur_pasien')
                ->save([
                  'no_rawat' => revertNorawat($id),
                  'kode' => $kode,
                  'status' => 'Ralan',
                  'prioritas' => $i+1
                ]);
            }

            $get_kd_jenis_prw = $_POST['kd_jenis_prw'];
            for ($i = 0; $i < count($get_kd_jenis_prw); $i++) {
                $kd_jenis_prw = $get_kd_jenis_prw[$i];
                $row = $this->db('rawat_jl_dr')->where('kd_jenis_prw', $kd_jenis_prw)->oneArray();
                $query = $this->db('rawat_jl_dr')
                  ->save([
                    'no_rawat' => revertNorawat($id),
                    'kd_jenis_prw' => $kd_jenis_prw,
                    'kd_dokter' => $_SESSION['opensimrs_username'],
                    'tgl_perawatan' => date('Y-m-d'),
                    'jam_rawat' => date('H:i:s'),
                    'material' => $row['material'],
                    'bhp' => $row['bhp'],
                    'tarif_tindakandr' => $row['tarif_tindakandr'],
                    'kso' => $row['kso'],
                    'menejemen' => $row['menejemenje'],
                    'biaya_rawat' => $row['biaya_rawat'],
                    'stts_bayar' => 'Belum'
                  ]);
            }

            $query = $this->db('catatan_perawatan')
              ->save([
                'tanggal' => date('Y-m-d'),
                'jam' => date('H:i:s'),
                'no_rawat' => revertNorawat($id),
                'kd_dokter' => $_SESSION['opensimrs_username'],
                'catatan' => $_POST['catatan']
              ]);

            if ($query) {
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function postRadiologiSave($id = null)
    {
        $errors = 0;
        $location = url([ADMIN, 'dokter_ralan', 'view', $id]);

        if (!$errors) {
            unset($_POST['save']);
            $no_order = $this->core->setNoOrderRad();
            $query = $this->db('permintaan_radiologi')
              ->save([
                'noorder' => $no_order,
                'no_rawat' => revertNorawat($id),
                'tgl_permintaan' => date('Y-m-d'),
                'jam_permintaan' => date('H:i:s'),
                'tgl_sampel' => '0000-00-00',
                'jam_sampel' => '00:00:00',
                'tgl_hasil' => '0000-00-00',
                'jam_hasil' => '00:00:00',
                'dokter_perujuk' => $_SESSION['opensimrs_username'],
                'status' => 'ralan',
                'informasi_tambahan' => '-',
                'diagnosa_klinis' => $_POST['diagnosa_klinis']
              ]);

            if ($query) {
                for ($i = 0; $i < count($_POST['kd_jenis_prw']); $i++) {
                  $query = $this->db('permintaan_pemeriksaan_radiologi')
                    ->save([
                      'noorder' => $no_order,
                      'kd_jenis_prw' => $_POST['kd_jenis_prw'][$i],
                      'stts_bayar' => 'Belum'
                    ]);
                }
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function postLaboratoriumSave($id = null)
    {
        $errors = 0;
        $location = url([ADMIN, 'dokter_ralan', 'view', $id]);

        if (!$errors) {
            unset($_POST['save']);
            $no_order = $this->core->setNoOrderLab();
            $query = $this->db('permintaan_lab')
              ->save([
                'noorder' => $no_order,
                'no_rawat' => revertNorawat($id),
                'tgl_permintaan' => date('Y-m-d'),
                'jam_permintaan' => date('H:i:s'),
                'tgl_sampel' => '0000-00-00',
                'jam_sampel' => '00:00:00',
                'tgl_hasil' => '0000-00-00',
                'jam_hasil' => '00:00:00',
                'dokter_perujuk' => $_SESSION['opensimrs_username'],
                'status' => 'ralan',
                'informasi_tambahan' => '-',
                'diagnosa_klinis' => $_POST['diagnosa_klinis']
              ]);

            if ($query) {
                for ($i = 0; $i < count($_POST['kd_jenis_prw']); $i++) {
                  $query = $this->db('permintaan_pemeriksaan_lab')
                    ->save([
                      'noorder' => $no_order,
                      'kd_jenis_prw' => $_POST['kd_jenis_prw'][$i],
                      'stts_bayar' => 'Belum'
                    ]);
                }
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function postResepSave($id = null)
    {
        $errors = 0;
        $location = url([ADMIN, 'dokter_ralan', 'view', $id]);

        if (!$errors) {
            unset($_POST['save']);
            $no_resep = $this->core->setNoResep();
            $query = $this->db('resep_obat')
              ->save([
                'no_resep' => $no_resep,
                'tgl_perawatan' => date('Y-m-d'),
                'jam' => date('H:i:s'),
                'no_rawat' => revertNorawat($id),
                'kd_dokter' => $_SESSION['opensimrs_username'],
                'tgl_peresepan' => date('Y-m-d'),
                'jam_peresepan' => date('H:i:s'),
                'status' => 'ralan'
              ]);

            if ($query) {
                for ($i = 0; $i < count($_POST['kode_brng']); $i++) {
                  $this->db('resep_dokter')
                    ->save([
                      'no_resep' => $no_resep,
                      'kode_brng' => $_POST['kode_brng'][$i],
                      'jml' => $_POST['jml'][$i],
                      'aturan_pakai' => $_POST['aturan_pakai'][$i]
                    ]);
                }
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function postResepRacikanSave($id = null)
    {
        $errors = 0;
        $location = url([ADMIN, 'dokter_ralan', 'view', $id]);

        if (!$errors) {
            unset($_POST['save']);
            $no_resep = $this->core->setNoResep();
            $query = $this->db('resep_obat')
              ->save([
                'no_resep' => $no_resep,
                'tgl_perawatan' => date('Y-m-d'),
                'jam' => date('H:i:s'),
                'no_rawat' => revertNorawat($id),
                'kd_dokter' => $_SESSION['opensimrs_username'],
                'tgl_peresepan' => date('Y-m-d'),
                'jam_peresepan' => date('H:i:s'),
                'status' => 'ralan'
              ]);

            if ($query) {
              $no_racik = $this->db('resep_dokter_racikan')->where('no_resep', $no_resep)->count();
              $no_racik = $no_racik+1;
              $this->db('resep_dokter_racikan')
                ->save([
                  'no_resep' => $no_resep,
                  'no_racik' => $no_racik,
                  'nama_racik' => $_POST['nama_racik'],
                  'kd_racik' => $_POST['kd_racik'],
                  'jml_dr' => $_POST['jml_dr'],
                  'aturan_pakai' => $_POST['aturan_pakai'],
                  'keterangan' => $_POST['keterangan']
                ]);

                for ($i = 0; $i < count($_POST['kode_brng']); $i++) {
                  $kapasitas = $this->db('databarang')->where('kode_brng', $_POST['kode_brng'][$i])->oneArray();
                  $jml = $_POST['jml_dr']*$_POST['kandungan'][$i];
                  $jml = $jml/$kapasitas['kapasitas'];
                  $this->db('resep_dokter_racikan_detail')
                    ->save([
                      'no_resep' => $no_resep,
                      'no_racik' => $no_racik,
                      'kode_brng' => $_POST['kode_brng'][$i],
                      'p1' => '1',
                      'p2' => '1',
                      'kandungan' => $_POST['kandungan'][$i],
                      'jml' => $jml
                    ]);
                }
                $this->notify('success', 'Simpan sukes');
            } else {
                $this->notify('failure', 'Simpan gagal');
            }

            redirect($location);
        }

        redirect($location, $_POST);
    }

    public function getAjax()
    {
        header('Content-type: text/html');
        $show = isset($_GET['show']) ? $_GET['show'] : "";
        switch($show){
        	default:
          break;
          case "databarang":
          $rows = $this->db('databarang')->like('nama_brng', '%'.$_GET['nama_brng'].'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'kode_brng' => $row['kode_brng'],
                'nama_brng'  => $row['nama_brng']
            );
          }
          echo json_encode($array, true);
          break;
          case "aturan_pakai":
          $rows = $this->db('master_aturan_pakai')->like('aturan', '%'.$_GET['aturan'].'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'aturan'  => $row['aturan']
            );
          }
          echo json_encode($array, true);
          break;
          case "jns_perawatan":
          $rows = $this->db('jns_perawatan')->like('nm_perawatan', '%'.$_GET['nm_perawatan'].'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'kd_jenis_prw' => $row['kd_jenis_prw'],
                'nm_perawatan'  => $row['nm_perawatan']
            );
          }
          echo json_encode($array, true);
          break;
          case "jns_perawatan_lab":
          $rows = $this->db('jns_perawatan_lab')->like('nm_perawatan', '%'.$_GET['nm_perawatan'].'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'kd_jenis_prw' => $row['kd_jenis_prw'],
                'nm_perawatan'  => $row['nm_perawatan']
            );
          }
          echo json_encode($array, true);
          break;
          case "jns_perawatan_radiologi":
          $rows = $this->db('jns_perawatan_radiologi')->like('nm_perawatan', '%'.$_GET['nm_perawatan'].'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'kd_jenis_prw' => $row['kd_jenis_prw'],
                'nm_perawatan'  => $row['nm_perawatan']
            );
          }
          echo json_encode($array, true);
          break;
          case "icd10":
          $phrase = '';
          if(isset($_GET['s']))
            $phrase = $_GET['s'];

          $rows = $this->db('penyakit')->like('kd_penyakit', '%'.$phrase.'%')->orLike('nm_penyakit', '%'.$phrase.'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'kd_penyakit' => $row['kd_penyakit'],
                'nm_penyakit'  => $row['nm_penyakit']
            );
          }
          echo json_encode($array, true);
          break;
          case "icd9":
          $phrase = '';
          if(isset($_GET['s']))
            $phrase = $_GET['s'];

          $rows = $this->db('icd9')->like('kode', '%'.$phrase.'%')->orLike('deskripsi_panjang', '%'.$phrase.'%')->toArray();
          foreach ($rows as $row) {
            $array[] = array(
                'kode' => $row['kode'],
                'deskripsi_panjang'  => $row['deskripsi_panjang']
            );
          }
          echo json_encode($array, true);
          break;
        }
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
        $this->core->addJS(url([ADMIN, 'dokter_ralan', 'javascript']), 'footer');
    }

}
