<?php
/**
 * Implements the worker portions of the pecl/gearman library
 *
 * @author      Brian Moon <brian@moonspot.net>
 * @copyright   1997-Present Brian Moon
 * @package     GearmanManager
 *
 */
declare(ticks = 1);

namespace Dowedo\Core\Daemon\Adapters;

use Dowedo\Core\Daemon\Daemon;
use Phalcon\Queue\Beanstalk\Job;

class Beanstalk extends Daemon
{
    /**
     * Starts a worker for the PECL library
     *
     * @param   array $worker_list List of worker functions to add
     * @    array $timeouts list of worker timeouts to pass to server
     * @throws \Exception
     * @throws \GearmanException
     */
    protected function start_lib_worker($worker_list, $timeouts = [])
    {
        /* @var $queue \Phalcon\Queue\Beanstalk */
        $queue = di('queue');
        $start = time();

        //watch tube for worker_list
        foreach ($worker_list as $worker) {
            $this->log("Watching tube: $worker", self::LOG_LEVEL_WORKER_INFO);
            $queue->watch($worker);
        }

        // 扫描等待队列
        while (!$this->stop_work) {
            /* @var $job \Phalcon\Queue\Beanstalk\Job */
            if ($job = $queue->reserve(1)) { //需要加上超时时间，否则阻塞之后，信号没法处理了。
                $result = $this->do_job($job);
                if ($result !== false) {
                    $job->delete(); // delete it
                } else {
                    $job->release(); // put back
                }
            }

            /**
             * Check the running time of the current child. If it has
             * been too long, stop working.
             */
            if ($this->max_run_time > 0 && time() - $start > $this->max_run_time) {
                $this->log("Been running too long, exiting", self::LOG_LEVEL_WORKER_INFO);
                $this->stop_work = true;
            }

            if (!empty($this->config["max_runs_per_worker"]) && $this->job_execution_count >= $this->config["max_runs_per_worker"]) {
                $this->log("Ran $this->job_execution_count jobs which is over the maximum({$this->config['max_runs_per_worker']}), exiting", Daemon::LOG_LEVEL_WORKER_INFO);
                $this->stop_work = true;
            }
        }
    }

    /**
     * Wrapper function handler for all registered functions
     * This allows us to do some nice logging when jobs are started/finished
     * @param Job $job
     * @return
     */
    public function do_job(Job $job)
    {
        static $objects;

        $stats = $job->stats();

        $tube = $stats['tube'];
        $id   = $job->getId();
        $data = $job->getBody();

        // Not a valid job name
        if (!isset($this->functions[$tube])) {
            $this->log("(jobId=$id) Function $tube is not a registered job name");
            return false;
        }

        // init object container
        if ($objects === null) {
            $objects = [];
        }

        //
        if ($this->prefix) {
            $class = $this->prefix . $tube;
        } else {
            $class = $tube;
        }
        $this->log("(jobId=$id) Function $class will be used to process this job");

        // prepare
        if (empty($objects[$tube]) && class_exists($class) && method_exists($class, "run")) {
            $this->log("(jobId=$id) Creating a $class object", self::LOG_LEVEL_WORKER_INFO);
            $objects[$tube] = new $class();

            // 初始化任务进程的运行环境
            if (method_exists($objects[$tube], 'init')) {
                $this->log("call init() for $tube");
                $objects[$tube]->init();
            }
        }

        $this->log("(jobId=$id) Starting Job: $tube", self::LOG_LEVEL_WORKER_INFO);
        $this->log("(jobId=$id) Workload: " . json_encode($data), self::LOG_LEVEL_DEBUG);

        $log = [];

        /**
         * Run the real function here
         */
        if (isset($objects[$tube])) {
            $this->log("(jobId=$id) Calling object for $tube.", self::LOG_LEVEL_DEBUG);
            // hooks
            if (method_exists($objects[$tube], 'beforeRun')) {
                //$this->log("call beforeRun() for $tube");
                $objects[$tube]->beforeRun($log);
            }

            // do real job
            $result = $objects[$tube]->run($data, $log);

            // hooks
            if (method_exists($objects[$tube], 'afterRun')) {
                //$this->log("call afterRun() for $tube");
                $objects[$tube]->afterRun($log);
            }
        } elseif (function_exists($class)) {
            $this->log("(jobId=$id) Calling function for $tube.", self::LOG_LEVEL_DEBUG);
            $result = $class($data, $log);
        } else {
            $this->log("(jobId=$id) FAILED to find a function or class for $tube.", self::LOG_LEVEL_INFO);
            return false;
        }

        if (!empty($log)) {
            foreach ($log as $l) {
                if (!is_scalar($l)) {
                    $l = explode("\n", trim(print_r($l, true)));
                } elseif (strlen($l) > 256) {
                    $l = substr($l, 0, 256) . "...(truncated)";
                }

                if (is_array($l)) {
                    foreach ($l as $ln) {
                        $this->log("(jobId=$id) $ln", self::LOG_LEVEL_WORKER_INFO);
                    }
                } else {
                    $this->log("(jobId=$id) $l", self::LOG_LEVEL_WORKER_INFO);
                }
            }
        }

        $result_log = $result;

        if (!is_scalar($result_log)) {
            $result_log = explode("\n", trim(print_r($result_log, true)));
        } elseif (strlen($result_log) > 256) {
            $result_log = substr($result_log, 0, 256) . "...(truncated)";
        }

        if (is_array($result_log)) {
            foreach ($result_log as $ln) {
                $this->log("(jobId=$id) $ln", self::LOG_LEVEL_DEBUG);
            }
        } else {
            $this->log("(jobId=$id) $result_log", self::LOG_LEVEL_DEBUG);
        }

        /**
         * Workaround for PECL bug #17114
         * http://pecl.php.net/bugs/bug.php?id=17114
         */
//        $type = gettype($result);
//        settype($result, $type);

        $this->job_execution_count ++;

        return $result;
    }

    /**
     * Validates Job workers
     */
    protected function validate_lib_workers()
    {
        foreach ($this->functions as $func => $props) {
            if ((!class_exists($props['class_name']) || !method_exists($props['class_name'], "run"))) {
                $this->log("Function $func not found in " . $props["path"]);
                posix_kill($this->pid, SIGUSR2);
                exit();
            }
        }
    }
}