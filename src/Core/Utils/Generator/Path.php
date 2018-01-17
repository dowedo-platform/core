<?php
namespace Carter\Core\Utils\Generator;
use Carter\Core\Utils\Generator\Exceptions\BuilderException;
use Phalcon\Config;
/**
 * Path Class
 */
class Path
{
    protected $rootPath = null;

    public function __construct($rootPath = null)
    {
        $this->rootPath = $rootPath ?: realpath('.') . DIRECTORY_SEPARATOR;
    }

    /**
     * Tries to find the current configuration in the application
     *
     * 生成模型只需如下几个参数：
     * 'database' => array(
     *    'adapter'     => 'Mysql',
     *    'host'        => '192.168.0.17',
     *    'username'    => 'dolphin',
     *    'password'    => 'dolphin123456',
     *    'dbname'      => 'dolphin',
     *    'charset'     => 'utf8',
     *    ),
     *
     * @return Config
     * @throws BuilderException
     */
    public function getConfig()
    {
        return new Config([]);

        // Read from app/config/database.php
        $configPath = $this->rootPath . 'config/database.php';
        if (!file_exists($configPath)) {
            throw new BuilderException("Builder can't locate the configuration file, it shoule be $configPath");
        }

        // 加载配置文件
        $config = include($configPath);

        // 只使用主数据库的配置
        if (is_array($config) && isset($config['database']['db'])) {
            $cloneConfig['database'] = $config['database']['db'];
            return new Config($cloneConfig);
        }

        throw new BuilderException("Builder can't load database config.");
    }

    public function setRootPath($path)
    {
        $this->rootPath = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;

        return $this;
    }

    public function getRootPath($pathPath = null)
    {
        return $this->rootPath . ($pathPath ? trim($pathPath, '\\/') . DIRECTORY_SEPARATOR : '');
    }

    public function appendRootPath($pathPath)
    {
        $this->setRootPath($this->getRootPath() . rtrim($pathPath, '\\/') . DIRECTORY_SEPARATOR);
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
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (preg_match('/^[A-Z]:\\\\/', $path)) {
                return true;
            }
        } else {
            if (substr($path, 0, 1) == DIRECTORY_SEPARATOR) {
                return true;
            }
        }

        return false;
    }
}
