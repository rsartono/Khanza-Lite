<?php

namespace Plugins\Settings;

use Systems\AdminModule;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use Plugins\Settings\Inc\RecursiveDotFilterIterator;

class Admin extends AdminModule
{
    private $assign = [];

    public function navigation()
    {
        return [
            'Aplikasi'          => 'general',
            'Jadwal Dokter'          => 'jadwal_dokter',
        ];
    }

    public function getGeneral()
    {
        $settings = $this->db('setting')->toArray();
        $settings['system'] = [
            'php'           => PHP_VERSION,
            'sqlite'        => $this->db()->pdo()->query('SELECT VERSION() as version')->fetch()[0],
            'sqlite_size'        => $this->roundSize($this->db()->pdo()->query("SELECT ROUND(SUM(data_length + index_length), 1) FROM information_schema.tables WHERE table_schema = '".DBNAME."' GROUP BY table_schema")->fetch()[0]),
            'system_size'   => $this->roundSize($this->_directorySize(BASE_DIR)),
        ];

        if (!empty($redirectData = getRedirectData())) {
            $settings = array_merge($settings, $redirectData);
        }

        $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));

        return $this->draw('general.html');
    }

    public function postSaveGeneral()
    {
        unset($_POST['save']);
        $_POST['logo'] = $this->db('setting')->select('logo')->oneArray();
        $_POST['wallpaper'] = $this->db('setting')->select('wallpaper')->oneArray();

        if (checkEmptyFields(['nama_instansi', 'alamat_instansi'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect(url([ADMIN, 'settings', 'general']), $_POST);
        } else {
            $errors = 0;

            $this->core->db()->pdo()->exec("TRUNCATE TABLE `setting`");
            $this->db('setting')->save($_POST);

            if (!$errors) {
                $this->notify('success', 'Pengaturan sukses');
            } else {
                $this->notify('failure', 'Pengaturan gagal');
            }

            redirect(url([ADMIN, 'settings', 'general']));
        }
    }

    private function _updateSettings($field, $value)
    {
        return $this->settings('settings', $field, $value);
    }

    private function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }

    private function _directorySize($path)
    {
        $bytestotal = 0;
        $path = realpath($path);
        if ($path!==false) {
            foreach (new RecursiveIteratorIterator(new RecursiveDotFilterIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS))) as $object) {
                try {
                    $bytestotal += $object->getSize();
                } catch (\Exception $e) {
                }
            }
        }

        return $bytestotal;
    }

    private function roundSize($bytes)
    {
        if ($bytes/1024 < 1) {
            return $bytes.' B';
        }
        if ($bytes/1024/1024 < 1) {
            return round($bytes/1024).' KB';
        }
        if ($bytes/1024/1024/1024 < 1) {
            return round($bytes/1024/1024, 2).' MB';
        } else {
            return round($bytes/1024/1024/1024, 2).' GB';
        }
    }
}
