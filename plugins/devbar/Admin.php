<?php

namespace Plugins\Devbar;

use Systems\AdminModule;

class Admin extends AdminModule
{
    private $timer = 0;

    public function __construct(\Systems\Main $core)
    {
        parent::__construct($core);

        $this->timer = -microtime(true);
    }
    public function init()
    {
        if (DEV_MODE && strpos(get_headers_list('Content-Type'), 'text/html') !== false) {
            $this->core->addCSS(url(MODULES.'/devbar/css/style.css?ver={?= time() ?}'));
        }
    }

    public function finish()
    {
        if (DEV_MODE && strpos(get_headers_list('Content-Type'), 'text/html') !== false) {
            $a = \debug_backtrace();
            foreach (Dump::$data as &$d) {
                $d['value'] = \htmlspecialchars($this->tpl->noParse($d['value']));
            }

            echo $this->draw(MODULES.'/devbar/view/bar.html', [
                'devbar' => [
                    'version' => $this->options->get('settings.version'),
                    'timer' => round(($this->timer + microtime(true))*1000, 2),
                    'memory' => round(memory_get_usage()/1024/1024, 2),
                    'database' => \Systems\Lib\QueryBuilder::lastSqls(),
                    'requests' => [
                        '$_GET' => ['print' => print_r($_GET, true), 'count' => count($_GET)],
                        '$_POST' => ['print' => print_r($_POST, true), 'count' => count($_POST)],
                        '$_COOKIE' => ['print' => print_r($_COOKIE, true), 'count' => 0],
                        '$_SERVER' => ['print' => print_r($_SERVER, true), 'count' => 0],
                    ],
                    'dump' => Dump::$data,
                    'sqlite' => $this->db()->pdo()->query('SELECT VERSION() as version')->fetch()[0],
                    'modules' => array_keys($this->core->module->getArray()),
                ],
            ]);
        }
    }
}

require_once('functions.php');
