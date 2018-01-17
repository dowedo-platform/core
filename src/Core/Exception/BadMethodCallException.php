<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Carter\Core\Exception;

/**
 * Bad Method Call Exception
 *
 * Exception thrown if a callback refers to an undefined method or if some arguments are missing.
 *
 * @package Carter\Core\Exception
 */
class BadMethodCallException extends BadFunctionCallException
{
    /**
     * @var int
     */
    protected $statusCode = 405;
}
