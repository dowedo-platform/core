<?php
/**
 * Created by PhpStorm.
 * User: nishurong
 * Date: 15/8/25
 * Time: 下午3:20
 */


namespace Carter\Core;


use Carter\Core\Console\Output\ConsoleOutput;
use Phalcon\Text;

/**
 * Class Task
 * @package Carter\Core
 */
abstract class Task extends Service
{
    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     *
     */
    public function onConstruct()
    {
        $this->output = di("output");
    }

    /**
     * @param string $usage
     */
    public function showUsage($usage = '')
    {
        $task = $this->dispatcher->getTaskName();
        $action = $this->dispatcher->getActionName();
        $this->output->writeln("$task:$action $usage");
        exit(0);
    }

    /**
     * @param $string
     */
    public function writeln($string)
    {
        $this->output->writeln($string);
    }

    /**
     * @param $string
     */
    public function writeError($string)
    {
        $this->output->writelnError($string);
    }

    /**
     * @param $string
     */
    public function writeInfo($string)
    {
        $this->output->writelnInfo($string);
    }

    public function helpAction()
    {
        $this->writeln("Show Help:");
        $class = get_class($this);
        $methods = get_class_methods($class);

        $annotations = di("annotations")->get($class);
        if ($annotations->getMethodsAnnotations()) {
            foreach ($annotations->getMethodsAnnotations() as $method => $collect) {
                foreach ($collect->getAnnotations() as $annotation)
                if (Text::endsWith($method, "Action", true)) {
                    $name = $annotation->getName();
                    $this->writeln($name);
                    $data = $annotation->getArgument(0);
                }
            }
        }


    }
} // End Task
