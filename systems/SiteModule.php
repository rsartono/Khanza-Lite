<?php

namespace Systems;

/**
 * Site class for website's module controller
 */
abstract class SiteModule extends BaseModule
{
    /**
     * Routes declarations for Site
     * Moved from __construct()
     *
     * @return void
     */
    public function routes()
    {
    }

    /**
     * Set route
     *
     * @param string $pattern
     * @param mixed $callback callable function or name of module method
     * @return void
     */
    protected function route($pattern, $callback)
    {
        if (is_callable($callback)) {
            $this->core->router->set($pattern, $callback);
        } else {
            $this->core->router->set($pattern, function () use ($callback) {
                return call_user_func_array([$this, $callback], func_get_args());
            });
        }
    }

    /**
     * Set site template
     *
     * @param string $file
     * @return void
     */
    protected function setTemplate($file)
    {
        $this->core->template = $file;
    }
}
