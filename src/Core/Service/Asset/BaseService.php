<?php
/**
 * Created by PhpStorm.
 * User: wolin
 * Date: 2017/4/10
 * Time: 10:22
 */

namespace Dowedo\Core\Service\Asset;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class BaseService extends \Phalcon\Mvc\User\Plugin
{
    static $instance = NULL;

    /**
     * Create Instance
     * @return static
     */
    static function getInstance(){
        if(static::$instance == NULL){
            $c = get_called_class();
            static::$instance = new $c();
        }

        return static::$instance;
    }

    private function __construct(){
    }

    private function __clone(){
    }

    protected function httpCore($url, $params='', $method = "POST", $cookies = [])
    {
        $appEnv = app_env();
        $url_prefix = self::getDI()->getConfig()->asset->$appEnv->url;

        $client = new Client(['verify' => false]);
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

        $resp = $this->Dowedo_data_handle($res);
        return $resp;
    }

    protected function httpCoreGet($url, $params='', $method = "GET", $cookies = []) {
        $resp = $this->httpCore($url, $params, $method, $cookies);

        if(! $resp['status']) {
            return false;
        }
        return $resp['data'];
    }

    protected function httpCoreForLogin($url, $params='', $method = "POST")
    {
        $appEnv = app_env();
        $url_prefix = self::getDI()->getConfig()->asset->$appEnv->url;

        $client = new Client(['verify' => false]);
        $jar = new CookieJar();
        $res = $client->request($method, $url_prefix . $url, [
            'json' => $params,
            'cookies' => $jar,
            'timeout' => 5,
        ]);

        $cookies = [];
        foreach ($jar->toArray() as $item){
            setcookie($item['Name'], $item['Value'], $item['Expires'], '/');
            $cookies[$item['Name']] = $item['Value'];
        }

        $this->logger->debug("login set Cookie: " . json_encode($cookies));
        /**
         * 用户登录后
         */
        $res->cookies = $cookies;
        return $res;
    }

    public function Dowedo_data_handle($data = [])
    {
        $code = $data->getStatusCode();
        $body = $data->getBody();
        $resp = [
            'status'  => false,
            'success' => false,
            'data'    => '',
            'msg'     => (200 == $code?'':'返回内容为空， 返回 code 为 ' . $code),
        ];
        $body = json_decode($body, true);
        if(isset($body['logged'])){
            $body['status'] = $body['logged'];
        }
        if(isset($body['status'])){
            $resp = $body;
            if('error' === $resp['status']){
                $resp['status'] = false;
                $resp['msg'] = $resp['message'];
            }else if ('ok' === $resp['status'] || 'success' === $resp['status']){
                $resp['status'] = true;
            }
            $resp['success'] = $resp['status'];
        }

        return $resp;
    }

    public function httpCoreWithoutLogin($url, $params='', $method = "POST"){
        $appEnv = app_env();
        $url_prefix = self::getDI()->getConfig()->asset->$appEnv->url;
        $client = new Client(['verify' => false]);
        $res = $client->request($method, $url_prefix . $url, [
            'json' => $params,
            'timeout' => 5,
        ]);
        $resp = $this->Dowedo_data_handle($res);
        return $resp;
    }
}