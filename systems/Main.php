<?php

namespace Systems;

use Systems\Lib\QueryBuilder;
use Systems\Lib\Templates;
use Systems\Lib\Router;

abstract class Main
{
    public $tpl;
    public $router;
    public $appends = [];
    public $module = null;
    protected static $userCache = [];

    public function __construct()
    {
        $this->setSession();

        QueryBuilder::connect("mysql:host=".DBHOST.";port=".DBPORT.";dbname=".DBNAME."",DBUSER, DBPASS);

        $check_db = $this->db()->pdo()->query("SHOW TABLES LIKE 'lite_modules'");
        $check_db->execute();
        $check_db = $check_db->fetch();

        if(empty($check_db)) {
            $this->freshInstall();
        }

        $this->tpl = new Templates($this);
        $this->router = new Router;

        $this->append(base64_decode('PG1ldGEgbmFtZT0iZ2VuZXJhdG9yIiBjb250ZW50PSJQaW5rTUVEIiAvPg=='), 'header');
    }

    public function db($table = null)
    {
        return new QueryBuilder($table);
    }

    public function getSettings($parameter)
    {
        $settings = $this->db('setting')->toArray();
        return $settings[0][$parameter];
    }

    private function setSession()
    {
        ini_set('session.use_only_cookies', 1);
        session_name('opensimrs');
        session_set_cookie_params(0, (opensimrs_dir() === '/' ? '/' : opensimrs_dir().'/'));
        session_start();
    }

    public function setNotify($type, $text, $args = null)
    {
        $variables = [];
        $numargs = func_num_args();
        $arguments = func_get_args();

        if ($numargs > 1) {
            for ($i = 1; $i < $numargs; $i++) {
                $variables[] = $arguments[$i];
            }
            $text = call_user_func_array('sprintf', $variables);
            $_SESSION[$arguments[0]] = $text;
        }
    }

    public function getNotify()
    {
        if (isset($_SESSION['failure'])) {
            $result = ['text' => $_SESSION['failure'], 'type' => 'danger'];
            unset($_SESSION['failure']);
            return $result;
        } elseif (isset($_SESSION['success'])) {
            $result = ['text' => $_SESSION['success'], 'type' => 'success'];
            unset($_SESSION['success']);
            return $result;
        } else {
            return false;
        }
    }

    public function addCSS($path)
    {
        $this->appends['header'][] = "<link rel=\"stylesheet\" href=\"$path\">\n";
    }

    public function addJS($path, $location = 'header')
    {
        $this->appends[$location][] = "<script src=\"$path\"></script>\n";
    }

    public function append($string, $location)
    {
        $this->appends[$location][] = $string."\n";
    }

    public function loginCheck()
    {
        if (isset($_SESSION['opensimrs_user']) && isset($_SESSION['token']) && isset($_SESSION['userAgent']) && isset($_SESSION['IPaddress'])) {
            if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR']) {
                return false;
            }
            if ($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT']) {
                return false;
            }

            if (empty(parseURL(1))) {
                redirect(url([ADMIN, 'dashboard', 'main']));
            } elseif (!isset($_GET['t']) || ($_SESSION['token'] != @$_GET['t'])) {
                return false;
            }

            return true;
        } elseif (isset($_COOKIE['opensimrs_remember'])) {
            $token = explode(":", $_COOKIE['opensimrs_remember']);
            if (count($token) == 2) {
                $row = $this->db('lite_roles')->leftJoin('lite_remember_me', 'lite_remember_me.user_id = lite_roles.id')->where('lite_roles.id', $token[0])->where('lite_remember_me.token', $token[1])->select(['lite_roles.*', 'lite_remember_me.expiry', 'token_id' => 'lite_remember_me.id'])->oneArray();

                if ($row) {
                    if (time() - $row['expiry'] > 0) {
                        $this->db('lite_remember_me')->delete(['id' => $row['token_id']]);
                    } else {
                        $_SESSION['opensimrs_user']   = $row['id'];
                        $_SESSION['opensimrs_username']   = $row['username'];
                        $_SESSION['token']      = bin2hex(openssl_random_pseudo_bytes(6));
                        $_SESSION['userAgent']  = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['IPaddress']  = $_SERVER['REMOTE_ADDR'];

                        $this->db('lite_remember_me')->where('lite_remember_me.user_id', $token[0])->where('lite_remember_me.token', $token[1])->save(['expiry' => time()+60*60*24*30]);

                        if (strpos($_SERVER['SCRIPT_NAME'], '/'.ADMIN.'/') !== false) {
                            redirect(url([ADMIN, 'dashboard', 'main']));
                        }

                        return true;
                    }
                }
            }
            setcookie('opensimrs_remember', null, -1, '/');
        }

        return false;
    }

    public function getUserInfo($field, $id = null, $refresh = false)
    {
        if (!$id) {
            $id = isset_or($_SESSION['opensimrs_user'], 0);
        }


        if (empty(self::$userCache) || $refresh) {
            //if($id == 1) {
                self::$userCache = $this->db('lite_roles')->where('id', $id)->oneArray();
            //} else {
            //    self::$userCache = $this->db('pegawai')->join('lite_roles', 'lite_roles.username = pegawai.nik', 'lite_roles.id = $id')->oneArray();
            //}
        }

        return self::$userCache[$field];
    }

    public function loadModules()
    {
        if ($this->module == null) {
            $this->module = new Lib\ModulesCollection($this);
        }
    }

    private function freshInstall()
    {
        QueryBuilder::connect("mysql:host=".DBHOST.";port=".DBPORT.";dbname=".DBNAME."",DBUSER, DBPASS);
        $pdo = QueryBuilder::pdo();

        $core = $this;

        $modules = unserialize(BASIC_MODULES);
        foreach ($modules as $module) {
            $file = MODULES.'/'.$module.'/Info.php';

            if (file_exists($file)) {
                $info = include($file);
                if (isset($info['install'])) {
                    $info['install']();
                }
            }
        }

        foreach ($modules as $order => $name) {
            $core->db('lite_modules')->save(['dir' => $name, 'sequence' => $order]);
        }

        redirect(url());
    }

}
