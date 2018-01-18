<?php
/**
 * Created by PhpStorm.
 * User: nishurong
 * Date: 15/8/17
 * Time: 下午7:42
 */


namespace Dowedo\Core;


/**
 * Class Job
 * @package Dowedo\Core
 */
abstract class Job
{
    /**
     *
     */
    public function init()
    {

    }

    /**
     * @param $log
     */
    public function beforeRun(&$log)
    {
        $log[] = get_class($this) . "::beforeRun() called";

        // 每次任务子进程内部重新设置数据库连接
        InitDatabase();
    }

    /**
     * @param $data
     * @param $log
     * @return mixed
     */
    abstract public function run($data, &$log);

    /**
     * @param $log
     */
    public function afterRun(&$log)
    {
        $log[] = get_class($this) . "::afterRun() called";

        // do something
        \ShutDatabase();
    }

} // End Job
