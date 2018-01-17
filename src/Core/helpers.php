<?php
/**
 * Created by PhpStorm.
 * User: Xueron
 * Date: 2015/7/21
 * Time: 16:07
 */
use Phalcon\Di;
use Phalcon\Text;
use Phalcon\Cli\Console;
use Phalcon\Mvc\Application;
use Symfony\Component\Yaml\Yaml;

if (!function_exists('di')) {
    /**
     * 返回一个DI容器，或者一个容器内的对象。
     *
     * @param null $name
     * @param array $parameters
     * @return mixed|\Phalcon\DI\FactoryDefault|Di\FactoryDefault\Cli
     */
    function di($name = null, $parameters = [])
    {
        // 利用 \Phalcon\Di 自带的 static 容器
        if (!$di = Di::getDefault()) {
            if (app_name() == 'cmd') {
                $di = new \Phalcon\Di\FactoryDefault\Cli();
            } else {
                $di = new \Phalcon\DI\FactoryDefault();
            }
        }

        if (!is_null($name)) {
            return $di->get($name, $parameters);
        }

        return $di;
    }
}

if (!function_exists('app')) {
    /**
     * Get the available application or service.
     *
     * @param  array $parameters
     * @return \Phalcon\Mvc\Application|Phalcon\Cli\Console
     */
    function app($parameters = [])
    {
        if (!di()->has('application')) {
            if (app_name() == 'cmd') {
                di()->setShared('application', "\\Phalcon\\Cli\\Console");
            } else {
                di()->setShared('application', "\\Phalcon\\Mvc\\Application");
            }
        }
        return di('application', $parameters);
    }
}

if (!function_exists('service')) {
    /**
     * 利用DI容器注册和管理用户自定义的服务
     * @param $serviceName
     * @param null $parameters
     * @return callable
     */
    function service($serviceName, $parameters = null)
    {
        // $serviceName = Text::lower($serviceName);
        if (strpos($serviceName, "/") === false) {
            return false;
        }

        if (!di()->has($serviceName)) {
            list($module, $service) = explode("/", $serviceName, 2);
            $class = "\\Carter\\Modules\\" . ucfirst($module) . "\\Services\\" . ucfirst($service);
            di()->setShared($serviceName, $class);
        }
        return di($serviceName, $parameters);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return;
        }

        if (Text::startsWith($value, '"') && Text::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given object. Useful for chaining.
     *
     * @param  mixed $object
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (!function_exists('readCache')) {
    /**
     * 读取缓存的文件
     *
     * @param $cacheFile cache file path
     * @param bool $serialize
     * @return mixed|null
     */
    function readCache($cacheFile, $serialize = false)
    {
        if (file_exists($cacheFile) && $cache = include $cacheFile) {
            return true === $serialize ? unserialize($cache) : $cache;
        }

        return null;
    }
}

if (!function_exists('writeCache')) {
    /**
     * 写入缓存文件
     *
     *
     * @param $cacheFile
     * @param $content
     * @param bool $serialize
     * @return bool
     */
    function writeCache($cacheFile, $content, $serialize = false)
    {
        if ($cacheFile && $fh = fopen($cacheFile, 'w')) {
            if (true === $serialize) {
                fwrite($fh, "<?php return '" . serialize($content) . "';");
            } else {
                fwrite($fh, '<?php return ' . var_export($content, true) . ';');
            }
            fclose($fh);

            return true;
        }

        return false;
    }
}

if (!function_exists('app_root')) {
    /**
     * 获取应用程序部署根目录
     * @return mixed
     */
    function app_root()
    {
        return dirname(dirname(dirname(__FILE__)));
    }
}

if (!function_exists('app_name')) {
    /**
     * 获取应用程序的名称，可用的名称包括：web、admin、vendor、api、cmd
     * @return mixed
     */
    function app_name()
    {
        return env('APP_NAME', 'web');
    }
}

if (!function_exists('app_env')) {
    /**
     * 获取应用程序的环境，可用的环境包括：development、testing、production
     * @return mixed
     */
    function app_env()
    {
        return env('APP_ENV', 'development');
    }
}

if (!function_exists('is_json')) {
    /**
     * 判断一个字符串是否是合法的json字符串
     * @param $string
     * @return bool
     */
    function is_json($string)
    {
        if (is_string($string)) {
            @json_decode($string);
            return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
    }
}

if (!function_exists('do_round')) {
    /**
     * 返回字符串格式的两位小数的金额
     *
     * @param $amount
     * @return string
     */
    function do_round($amount)
    {
        return \Carter\Core\Currency\Money::create($amount)->round(2)->getAmount();
    }
}

if (!function_exists('throwException')) {
    /**
     * 抛出一个RuntimeException
     * @param $message
     * @throws \Carter\Core\Exception\RuntimeException
     */
    function throwException($message)
    {
        throw new \Carter\Core\Exception\RuntimeException($message);
    }
}

if (!function_exists("app_log")) {
    /**
     * 返回一个根据日期创建的日志适配器
     *
     * @param string $loggerName
     * @return \Phalcon\Logger\Adapter\File
     */
    function app_log($loggerName = 'Carter')
    {
        $date = date('Y-m-d');
        $path = di('config')->app->log->path . DIRECTORY_SEPARATOR . $date;
        if (!file_exists($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("创建目录 $path 失败");
            }
        }
        return new \Phalcon\Logger\Adapter\File($path . DIRECTORY_SEPARATOR . $loggerName . '.log');
    }
}

if (!function_exists("getUnbindCardUrl")) {
    /**
     * 返回用于解绑快捷卡的登录url
     */
    function getUnbindCardUrl()
    {
        if (app_env() == 'production') {
            return di('config')->chinapnr->production->userLoginUrl;
        } else {
            return di('config')->chinapnr->testing->unbindCardLocation;
        }
    }
}

if (!function_exists('getCurrentFrontUrl')) {
    /**
     * 返回当前前台url地址
     */
    function getCurrentFrontUrl()
    {
        switch (app_env()) {
            case 'local':
                $siteUrl = FRONT_SITE_LOCAL;
                break;
            case 'development':
                $siteUrl = FRONT_SITE_DEV;
                break;
            case 'testing':
                $siteUrl = FRONT_SITE_DEMO;
                break;
            default:
                $siteUrl = FRONT_SITE_PRO;
        }

        return $siteUrl;
    }
}

if (!function_exists('yaml_parse')) {
    function yaml_parse($input, $pos=0, &$ndocs=0, $callbacks = [])
    {
        return Yaml::parse($input);
    }
}
