<?php

namespace Plugins\Ralan;

use Systems\AdminModule;

class Admin extends AdminModule
{

    public function navigation()
    {
        return [
            'Manage' => 'manage',
        ];
    }

    public function getManage()
    {
        $this->_addHeaderFiles();
        $rows = $this->db('reg_periksa')->where('tgl_registrasi', date('Y-m-d'))->toArray();
        $this->assign['list'] = [];
        foreach ($rows as $row) {
            $row['editURL'] = url([ADMIN, 'ralan', 'edit', convertNorawat($row['no_rawat'])]);
            $this->assign['list'][] = $row;
        }

        $this->assign['title'] = 'Kelola Rawat Jalan';

        return $this->draw('manage.html', ['ralan' => $this->assign]);
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/ralan/js/admin/ralan.js');
        exit();
    }

    public function getCss()
    {
        header('Content-type: text/css');
        echo $this->draw(MODULES.'/ralan/css/admin/ralan.css');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addCSS(url([ADMIN, 'ralan', 'css']));
        $this->core->addJS(url([ADMIN, 'ralan', 'javascript']), 'footer');
    }

}
