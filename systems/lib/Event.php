<?php

namespace Systems\Lib;

/**
 * Events class
 */
class Event
{
    /** @var array */
    protected static $events = [];

    /**
     * Add new event handler
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public static function add($name, callable $callback)
    {
        static::$events[$name][] = $callback;
    }

    /**
     * Execute registered event handlers
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function call($name, array $params = [])
    {
        $return = true;
        foreach (isset_or(static::$events[$name], []) as $value) {
            $return = $return && (call_user_func_array($value, $params) !== false);
        }
        return $return;
    }
}
