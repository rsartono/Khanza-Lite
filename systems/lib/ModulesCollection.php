<?php

namespace Systems\Lib;

/**
 * OpenSIMRS modules collection
 */
class ModulesCollection
{
    /**
     * List of loaded modules
     *
     * @var array
     */
    protected $modules = [];

    /**
     * ModulesCollection constructor
     *
     * @param \Systems\Main $core
     */
    public function __construct($core)
    {
        $modules = array_column($core->db('modules')->asc('sequence')->toArray(), 'dir');
        if ($core instanceof \Systems\Admin) {
            $clsName = 'Admin';
        } else {
            $clsName = 'Site';
        }

        foreach ($modules as $dir) {
            $file = MODULES.'/'.$dir.'/'.$clsName.'.php';
            if (file_exists($file)) {
                $namespace = 'plugins\\'.$dir.'\\'.$clsName;
                $this->modules[$dir] = new $namespace($core);
            }
        }

        // Init loop
        $this->initLoop();

        // Routes loop for Site
        if ($clsName != 'Admin') {
            $this->routesLoop();
        }
    }

    /**
     * Executes all init methods
     *
     * @return void
     */
    protected function initLoop()
    {
        foreach ($this->modules as $module) {
            $module->init();
        }
    }

    /**
     * Executes all routes methods
     *
     * @return void
     */
    protected function routesLoop()
    {
        foreach ($this->modules as $module) {
            $module->routes();
        }
    }

    /**
     * Executes all finish methods
     *
     * @return void
     */
    public function finishLoop()
    {
        foreach ($this->modules as $module) {
            $module->finish();
        }
    }

    /**
     * Get list of modules as array
     *
     * @return array
     */
    public function getArray()
    {
        return $this->modules;
    }

    /**
     * Check if collection has loaded module
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->modules);
    }

    /**
     * Get specified module by magic method
     *
     * @param string $module
     * @return \Systems\BaseModule
     */
    public function __get($module)
    {
        if (isset($this->modules[$module])) {
            return $this->modules[$module];
        } else {
            return null;
        }
    }
}
