<?php

namespace Plugins\Users;

use Systems\AdminModule;

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

    /**
    * users list
    */
    public function getManage()
    {
        $rows = $this->db('lite_roles')->where('id', '!=', '1')->toArray();
        foreach ($rows as &$row) {
            if (empty($row['fullname'])) {
                $row['fullname'] = '----';
            }
            $row['editURL'] = url([ADMIN, 'users', 'edit', $row['id']]);
            $row['delURL']  = url([ADMIN, 'users', 'delete', $row['id']]);
        }

        return $this->draw('manage.html', ['myId' => $this->core->getUserInfo('id'), 'users' => $rows]);
    }


    /**
    * add new user
    */
    public function getAdd()
    {

        $this->_addInfoUser();
        $this->_addInfoRole();
        $this->_addInfoCap();

        if (!empty($redirectData = getRedirectData())) {
            $this->assign['form'] = filter_var_array($redirectData, FILTER_SANITIZE_STRING);
        } else {
            $this->assign['form'] = ['username' => '', 'role' => '', 'access' => ''];
        }

        $this->assign['title'] = 'Pengguna baru';
        $this->assign['modules'] = $this->_getModules('all');

        return $this->draw('form.html', ['users' => $this->assign]);
    }

    /**
    * edit user
    */
    public function getEdit($id)
    {
        $user = $this->db('lite_roles')->oneArray($id);

        $this->_addInfoUser();
        $this->_addInfoRole();
        $this->_addInfoCap();

        if (!empty($user)) {
            $this->assign['form'] = $user;
            $this->assign['title'] = 'Edit pengguna';
            $this->assign['modules'] = $this->_getModules($user['access']);

            return $this->draw('form.html', ['users' => $this->assign]);
        } else {
            redirect(url([ADMIN, 'users', 'manage']));
        }
    }

    /**
    * save user data
    */
    public function postSave($id = null)
    {
        $errors = 0;

        // location to redirect
        if (!$id) {
            $location = url([ADMIN, 'users', 'add']);
        } else {
            $location = url([ADMIN, 'users', 'edit', $id]);
        }

        // admin
        if ($id == 1) {
            $_POST['access'] = ['all'];
        }

        // check if required fields are empty
        if (checkEmptyFields(['username', 'access'], $_POST)) {
            $this->notify('failure', 'Isian kosong');
            redirect($location, $_POST);
        }

        // check if user already exists
        if ($this->_userAlreadyExists($id)) {
            $errors++;
            $this->notify('failure', 'Pengguna sudah ada');
        }
        // access to modules
        if ((count($_POST['access']) == count($this->_getModules())) || ($id == 1)) {
            $_POST['access'] = 'all';
        } else {
            $_POST['access'][] = 'dashboard';
            $_POST['access'] = implode(',', $_POST['access']);
        }

        // CREATE / EDIT
        if (!$errors) {
            unset($_POST['save']);

            if (!$id) {    // new
                $query = $this->db('lite_roles')->save($_POST);
            } else {        // edit
                $query = $this->db('lite_roles')->where('id', $id)->save($_POST);
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
    * remove user
    */
    public function getDelete($id)
    {
        if ($id != 1 && $this->core->getUserInfo('id') != $id && ($user = $this->db('lite_roles')->oneArray($id))) {
            if ($this->db('lite_roles')->delete($id)) {
                $this->notify('success', 'Hapus sukses');
            } else {
                $this->notify('failure', 'Hapus gagal');
            }
        }
        redirect(url([ADMIN, 'users', 'manage']));
    }

    /**
    * list of active modules
    * @return array
    */
    private function _getModules($access = null)
    {
        $result = [];
        $rows = $this->db('lite_modules')->toArray();

        if (!$access) {
            $accessArray = [];
        } else {
            $accessArray = explode(',', $access);
        }

        foreach ($rows as $row) {
            if ($row['dir'] != 'dashboard') {
                $details = $this->core->getModuleInfo($row['dir']);

                if (empty($accessArray)) {
                    $attr = '';
                } else {
                    if (in_array($row['dir'], $accessArray) || ($accessArray[0] == 'all')) {
                        $attr = 'selected';
                    } else {
                        $attr = '';
                    }
                }
                $result[] = ['dir' => $row['dir'], 'name' => $details['name'], 'icon' => $details['icon'], 'attr' => $attr];
            }
        }
        return $result;
    }

    /**
    * check if user already exists
    * @return array
    */
    private function _userAlreadyExists($id = null)
    {
        if (!$id) {    // new
            $count = $this->db('lite_roles')->where('username', $_POST['username'])->count();
        } else {        // edit
            $count = $this->db('lite_roles')->where('username', $_POST['username'])->where('id', '<>', $id)->count();
        }
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * module JavaScript
    */
    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/users/js/admin/users.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        // CSS
        $this->core->addCSS(url('assets/css/jquery-ui.css'));

        // JS
        $this->core->addJS(url('assets/jscripts/jquery-ui.js'), 'footer');

        // MODULE SCRIPTS
        $this->core->addJS(url([ADMIN, 'users', 'javascript']), 'footer');
    }

    private function _addInfoUser() {
        // get users
        $user = $this->db()->pdo()->prepare("SELECT AES_DECRYPT(id_user,'nur') as username FROM user");
        $user->execute();
        $user = $user->fetchAll();

        if (count($user)) {
          $this->assign['user'] = [];
          foreach($user as $row) {
              $this->assign['user'][] = $row;
          }
        }
    }

    private function _addInfoRole() {
      $role = array('admin','manajemen','medis','paramedis','apoteker','rekammedis','kasir');
      if (count($role)) {
        $this->assign['role'] = [];
        foreach($role as $row) {
            $row = trim($row);
            $this->assign['role'][] = $row;
        }
      }
    }

    private function _addInfoCap() {
      $cap = array('admin','manajemen','medis','paramedis','apoteker','rekammedis','kasir');
      if (count($cap)) {
        $this->assign['cap'] = [];
        foreach($cap as $row) {
            $row = trim($row);
            $this->assign['cap'][] = $row;
        }
      }
    }

}
