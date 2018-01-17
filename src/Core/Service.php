<?php
/**
 * Created by PhpStorm.
 * User: Xueron
 * Date: 2015/7/30
 * Time: 11:25
 */

namespace Carter\Core;

use GuzzleHttp\Cookie\CookieJar;
use League\Fractal\Pagination\Cursor;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\Injectable;

/**
 * Class Service
 * @package Carter\Core\Service
 * @property \Phalcon\Logger\Adapter\File|\Phalcon\Logger\AdapterInterface $logger
 * @property \Phalcon\Queue\Beanstalk $queue
 */
abstract class Service extends Injectable
{
    /**
     * 构造函数，初始化事件管理器，调用服务自己的初始化方法。
     */
    public final function __construct()
    {
        $this->setEventsManager(di('eventsManager'));

        if (method_exists($this, "onConstruct")) {
            $this->onConstruct();
        }
    }

    /**
     * 记录日志
     *
     * @param $message
     * @param string $type
     */
    public function actionLog($message, $type = 'info')
    {
        $class = get_class($this);
        $message = "[$class] " . $message;
        if (di('logger') != null) {
            di('logger')->$type($message);
        }
    }

    /**
     * 返回一个Isolated Transactions
     *
     * @return \Phalcon\Mvc\Model\Transaction
     */
    public function getTransaction()
    {
        $transaction = di('transactionManager')->get();

//        // 事务管理器
//        $currentNumber = di('transactionManager')->getNumber();
//        $currentTransactions = di('transactionManager')->getTransactionCount();
//        $this->actionLog(sprintf("事务启动，当前事务当前序号%d，当前事务栈数量%d", $currentNumber, $currentTransactions));
//
//        // 当前事务
//        $undexManager = $transaction->isManaged() ? "Y" : "N";
//        $valid = $transaction->isValid() ? "Y" : "N";
//        $this->actionLog(sprintf("事务信息，是否在管理器中：%s，是否有效事务：%s", $undexManager, $valid));
//
//        // 当前事务的连接
//        /** @var Mysql $connection */
//        $connection = $transaction->getConnection();
//        $connectionId = $connection->getConnectionId();
//        $txLevel = $connection->getTransactionLevel();
//        $isUndexTx = $connection->isUnderTransaction() ? "Y" : "N";
//        $this->actionLog(sprintf("连接信息：connId=%d, 事务层次=%d，是否在事务中：%s", $connectionId, $txLevel, $isUndexTx));

        return $transaction;
    }

    /**
     * 返回是否存在活动的事务
     * @return bool
     */
    public function hasTransaction()
    {
        return di('transactionManager')->has();
    }

    /**
     * 触发一个内部事件，可以在当前服务的实现内部调用
     *
     * Fires an event, implicitly calls behaviors and listeners in the events manager are notified
     * @param $eventName
     */
    public function fireEvent($eventName)
    {
        /**
         * Check if there is a method with the same name of the event
         */
        if (method_exists($this, $eventName)) {
            $this->$eventName();
        }

        /**
         * Send a notification to the events manager
         */
        return $this->fire($eventName, $this);
    }

    /**
     * 触发事件
     *
     * @param $eventType
     * @param $source
     * @param null $data
     */
    public function fire($eventType, $source, $data = null)
    {
        di('eventsManager')->fire($eventType, $source, $data);
    }

    /**
     * 创建一个当前请求的服务的示例
     *
     * Create Instance
     * @return static
     */
    public static function getInstance()
    {
        $className = get_called_class();
        return new $className();
    }

    /**
     * @param $data
     * @param callable|\League\Fractal\TransformerAbstract $transformer
     * @param string|null $resourceKey
     * @param Cursor|null $cursor
     * @param array $meta
     * @return \League\Fractal\Scope
     */
    public function collection($data, $transformer, $resourceKey = null, Cursor $cursor = null, $meta = [])
    {
        $resource = new Collection($data, $transformer, $resourceKey);

        foreach ($meta as $metaKey => $metaValue) {
            $resource->setMetaValue($metaKey, $metaValue);
        }

        if (!is_null($cursor)) {
            $resource->setCursor($cursor);
        }

        $rootScope = di('transformerManager')->createData($resource);

        return $rootScope;
    }

    /**
     * @param $data
     * @param callable|\League\Fractal\TransformerAbstract $transformer
     * @param string|null $resourceKey
     * @param array $meta
     * @return \League\Fractal\Scope
     */
    public function item($data, $transformer, $resourceKey = null, $meta = [])
    {
        $resource = new Item($data, $transformer, $resourceKey);

        foreach ($meta as $metaKey => $metaValue) {
            $resource->setMetaValue($metaKey, $metaValue);
        }

        $rootScope = di('transformerManager')->createData($resource);

        return $rootScope;
    }

    public function mergeColumns($prefix, $columns)
    {
        $result = [];
        foreach ($columns as $column) {
            $result[] = $prefix . '.' . $column;
        }
        return $result;
    }

    public function error($msg = '')
    {
        return [
            'status' => false,
            'msg' => $msg,
        ];
    }

    public function success($data)
    {
        return [
            'status' => true,
            'data' => $data,
        ];
    }

    protected function httpCore($url, $params='', $method = "POST", $cookies = [])
    {
        $appEnv = app_env();
        $url_prefix = self::getDI()->getConfig()->asset->$appEnv->url;

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $jar = new CookieJar();

        if(!empty($_COOKIE)){
            $this->logger->debug("_COOKIE: " . json_encode($_COOKIE));
            $jar->clear();
            foreach ($_COOKIE as $key => $value) {
                $newcookie = \GuzzleHttp\Cookie\SetCookie::fromString('');
                $newcookie->setName($key);
                $newcookie->setValue($value);
                $newcookie->setDomain(str_replace(array('https://','http://'),"",$url_prefix));
                $jar->setCookie($newcookie);
            }
        }
        // login后立即获取用户信息传递来的cookie，此时$_COOKIE无效
        if(!empty($cookies)){
            $this->logger->debug("cookies: " . json_encode($cookies));
            $jar->clear();
            foreach ($cookies as $key => $value) {
                $newcookie = \GuzzleHttp\Cookie\SetCookie::fromString('');
                $newcookie->setName($key);
                $newcookie->setValue($value);
                $newcookie->setDomain(str_replace(array('https://','http://'),"",$url_prefix));
                $jar->setCookie($newcookie);
            }
        }

        $this->logger->debug("http send Cookie: " . json_encode($jar->toArray()));
        $res = $client->request($method, $url_prefix . $url, [
            'json' => $params,
            'cookies' => $jar,
            'timeout' => 5,
        ]);

        if(empty($_COOKIE)) {
            foreach ($jar->toArray() as $item) {
                setcookie($item['Name'], $item['Value'], $item['Expires'], '/');
                $this->logger->debug("return cookies: " . $item['Name'] . ' ' . $item['Value']);
            }
        }
        return $res;
    }
}
