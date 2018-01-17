<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Carter\Core\Exception;

use Phalcon\Exception as PhalconException;

/**
 * Standard Exception, default status code is 500
 * @package Carter\Core\Exception
 */
class StandardException extends PhalconException implements ExceptionInterface
{
    /**
     * @var int
     */
    protected $statusCode = 500;

    /**
     *
     * @param string $message
     * @param int $code
     * @param null|int|\Exception $previous ,  when $previous is int,will use as status code
     * @param null $statusCode
     */
    public function __construct($message, $code = 10000, $previous = null, $statusCode = null)
    {
        //Allow the third paramater to be statuscode
        if (is_numeric($previous)) {
            $statusCode = $previous;
            $previous = null;
        }

        if ($statusCode && is_numeric($statusCode) && $statusCode > 99 && $statusCode < 600) {
            $this->statusCode = $statusCode;
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int|string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
