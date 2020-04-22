<?php

namespace Systems;

/**
 * Base class for each module functionality
 */
class BaseModule
{
    /**
     * Reference to Core instance
     *
     * @var \Systems\Main
     */
    protected $core;

    /**
     * Reference to Template instance
     *
     * @var \Systems\Lib\Templates
     */
    protected $tpl;

    /**
     * Reference to Router instance
     *
     * @var \Systems\Lib\Router
     */
    protected $route;

    /**
     * Reference to Settings instance
     *
     * @var \Systems\Lib\Settings
     */
    protected $settings;

    /**
     * Module dir name
     *
     * @var string
     */
    protected $name;

    /**
     * Module constructor
     *
     * @param Systems\Main $core
     * @return void
     */
    public function __construct(Main $core)
    {
        $this->core = $core;
        $this->tpl = $core->tpl;
        $this->router = $core->router;
        $this->settings = $core->settings;
        $this->name = strtolower(str_replace(['Plugins\\', '\\Admin', '\\Site'], null, static::class));
    }

    /**
     * Module initialization
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * Procedures before destroy
     *
     * @return void
     */
    public function finish()
    {
    }

    /**
     * Hook to draw template with set variables
     *
     * @param string $file
     * @param array $variables
     * @return string
     */
    protected function draw($file, array $variables = [])
    {
        if (!empty($variables)) {
            foreach ($variables as $key => $value) {
                $this->tpl->set($key, $value);
            }
        }

        if (strpos($file, BASE_DIR) !== 0) {
            if ($this instanceof AdminModule) {
                $file = MODULES.'/'.$this->name.'/view/admin/'.$file;
            } else {
                $file = MODULES.'/'.$this->name.'/view/'.$file;
            }
        }

        return $this->tpl->draw($file);
    }

    /**
     * Get or set module settings
     *
     * @param string $module Example 'module' or shorter 'module.field'
     * @param mixed $field If module has field it contains value
     * @param mixed $value OPTIONAL
     * @return mixed
     */
    protected function settings($module, $field = false, $value = false)
    {
        if (substr_count($module, '.') == 1) {
            $value = $field;
            list($module, $field) = explode('.', $module);
        }

        if ($value === false) {
            return $this->settings->get($module, $field);
        } else {
            return $this->settings->set($module, $field, $value);
        }
    }

    /**
     * Database QueryBuilder
     *
     * @param string $table
     * @return \Systems\Lib\QueryBuilder
     */
    protected function db($table = null)
    {
        return $this->core->db($table);
    }

    /**
    * Create notification
    * @param string $type ('success' or 'failure')
    * @param string $text
    * @param mixed $args [, mixed $... ]]
    * @return void
    */
    protected function notify()
    {
        call_user_func_array([$this->core, 'setNotify'], func_get_args());
    }
}
