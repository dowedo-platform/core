<?php


namespace Dowedo\Core\Api;

use EllipseSynergie\ApiResponse\AbstractResponse;
use Phalcon\Http\ResponseInterface;

/**
 * Class Response
 * @package Dowedo\Core\Api
 */
class Response extends AbstractResponse
{

    /**
     * @var ResponseInterface
     */
    protected $phalconResponse;

    /**
     * @param array $array
     * @param array $headers
     * @return \Phalcon\Http\Response
     */
    public function withArray(array $array, array $headers = [])
    {
        //$this->phalconResponse->setJsonContent($array, JSON_NUMERIC_CHECK);
        $this->phalconResponse->setJsonContent($array);

        if ($this->getStatusCode() != 200) {
            $this->phalconResponse->setStatusCode($this->getStatusCode(), $array['reason']);//$array["error"]["message"]);
        } else {
            $this->phalconResponse->setStatusCode($this->getStatusCode(), "OK");
        }


        if (isset($headers)) {
            foreach ($headers as $key => $value) {
                $this->phalconResponse->setHeader($key, $value);
            }
        }
        $this->phalconResponse->setContentType('application/json');

        return $this->phalconResponse;
    }

    public function withRaw($data, array $headers = [])
    {
        //$this->phalconResponse->setJsonContent($array, JSON_NUMERIC_CHECK);
        $this->phalconResponse->setContent($data);
        $this->phalconResponse->setStatusCode($this->getStatusCode(), "OK");

        if (isset($headers)) {
            foreach ($headers as $key => $value) {
                $this->phalconResponse->setHeader($key, $value);
            }
        }
        $this->phalconResponse->setContentType('application/json');

        return $this->phalconResponse;
    }

    /**
     * @param ResponseInterface $phalconResponse
     */
    public function setPhalconResponse(ResponseInterface $phalconResponse)
    {
        $this->phalconResponse = $phalconResponse;
    }
}

