<?php
namespace Dowedo\Core\Mvc;

use Dowedo\Core\Api\Response;
use Dowedo\Core\Exception\VerifyFailedException;
use Phalcon\Mvc\Controller as ControllerBase;
use League\Fractal\Resource\Collection;

/**
 * Class Controller
 * @package Dowedo\Core\Mvc
 * @property Response $apiResponse
 */
abstract class Controller extends ControllerBase
{
    public function appLog($message, $type = 'info')
    {
        if (is_array($message)) {
            $message = var_export($message, true);
        }
        di("appLogger")->$type($message);
    }

    /**
     * 设置校验
     */
    public function setToken()
    {
        $secretKey = di("security")->getTokenKey();
        $secretValue = di("security")->getToken();
        $this->view->setVars([
            'secretKey' => $secretKey,
            'secretValue' => $secretValue
        ]);
    }

    /**
     * 校验请求
     *
     * @throws VerifyFailedException
     */
    public function checkToken()
    {
        if (!di("security")->checkToken()) {
            throw new VerifyFailedException("检测到不合法请求，请重新尝试");
        }
    }


    protected function success($data = null, $msg = '')
    {
        return $this->apiResponse->withArray([
            'status' => true,
            'data' => $data,
            'msg' => $msg
        ]);
    }

    protected function error($msg = '', $data = null)
    {
        return $this->apiResponse->withArray([
            'status' => false,
            'data' => $data,
            'msg' => $msg
        ]);
    }

    protected function data($data)
    {
        return $this->apiResponse->withArray($data);
    }

    protected function rawData($data)
    {
        return $this->apiResponse->withRaw($data);
    }

    protected function rawDataFromArray($array, $attributes = null)
    {
        $data = json_encode($array);
        return $this->rawData($data);
    }

    protected function rawDataFromObject($object, $attributes = null)
    {
        $array = $object->toArray($attributes);
        return $this->rawDataFromArray($array, $attributes);
    }

    public function transformCollection($data, $transformer, $resourceKey = null, Cursor $cursor = null, $meta = [])
    {
        $resource = new Collection($data, $transformer, $resourceKey);

        foreach ($meta as $metaKey => $metaValue) {
            $resource->setMetaValue($metaKey, $metaValue);
        }

        if (!is_null($cursor)) {
            $resource->setCursor($cursor);
        }
        $rootScope = di('transformerManager')->createData($resource);
        return $rootScope->toArray("data");
    }

    protected function jsonResponse($params)
    {
        return $this->apiResponse->withArray([
            'status' => $params['status'],
            'msg' => $params['msg'],
            'data' => $params['data']
        ]);
    }

    protected function filterKeys($array, $keys)
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $result[$key] = $array[$key];
            }
        }
        return $result;
    }

    protected function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s');
    }
}
