<?php
/**
 * Created by PhpStorm.
 * User: Xueron
 * Date: 2015/7/30
 * Time: 11:56
 */

namespace Dowedo\Core\Mvc\View\Engine\Volt\Extension;


class PhpFunction
{
    /**
     * This method is called on any attempt to compile a function call
     */
    public function compileFunction()
    {
        $params = func_get_args();
        $name = array_shift($params);
        array_pop($params);
        if (function_exists($name)) {
            return $name . '('. join(", ", $params) . ')';
        }
    }
}