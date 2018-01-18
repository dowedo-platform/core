<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Utils                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2015 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Serghei Iakovlev <sadhooklay@gmail.com>                       |
  +------------------------------------------------------------------------+
*/

namespace Dowedo\Core\Utils\Generator;

use Phalcon\Config;

abstract class Component
{
    /**
     * Builder Options
     * @var Options
     */
    protected $options = null;

    /**
     * Path Component
     * @var Path
     */
    protected $path;

    /**
     * Create Builder object
     *
     * @param array $options Builder options
     */
    public function __construct(array $options = [])
    {
        $this->options = new Options($options);
        $this->path = new Path(realpath('.') . DIRECTORY_SEPARATOR);
    }

    protected function checkNamespace($namespace)
    {
        return true;
    }

    /**
     * Tries to find the current configuration in the application
     *
     * @param string $type Config type: ini | php
     * @return \Phalcon\Config
     * @throws BuilderException
     */
    protected function getConfig()
    {
        return $this->path->getConfig();
    }

    /**
     * Check if a path is absolute
     *
     * @param string $path Path to check
     *
     * @return bool
     */
    public function isAbsolutePath($path)
    {
        return $this->path->isAbsolutePath($path);
    }

    /**
     * Check if the script is running on Console mode
     *
     * @return boolean
     */
    public function isConsole()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * Check if the current adapter is supported by Phalcon
     *
     * @param  string $adapter
     *
     * @return bool
     * @throws BuilderException
     */
    public function isSupportedAdapter($adapter)
    {
        if (!class_exists('\Phalcon\Db\Adapter\Pdo\\' . $adapter)) {
            throw new BuilderException("Adapter $adapter is not supported");
        }

        return true;
    }

    abstract public function build();
}
