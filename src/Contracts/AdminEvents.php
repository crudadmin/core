<?php

namespace Admin\Core\Contracts;

use AdminCore;

trait AdminEvents
{
    /**
     * Register event.
     *
     * @param  string $name
     * @param  closure $callback
     * @return void
     */
    public function event($name, $callback)
    {
        $events = AdminCore::get('events', []);

        $events[$name][] = $callback;

        AdminCore::set('events', $events);
    }

    /**
     * Register class with events.
     *
     * @param  string $class
     * @return void
     */
    public function registerEvents($class)
    {
        $class = new $class;

        $class->register();
    }

    /**
     * Fire registered event.
     *
     * @param  string $name
     * @param  array  $args
     * @return void
     */
    public function fire($name, &$args = [])
    {
        $events = AdminCore::get('events', []);

        if (!isset($events[$name])) {
            return;
        }

        foreach ((array)$events[$name] as $key => $closure) {
            call_user_func_array($closure, $args);
        }
    }
}
