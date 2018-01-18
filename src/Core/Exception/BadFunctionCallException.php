<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Dowedo\Core\Exception;

/**
 * Class BadFunctionCallException
 *
 * Exception thrown if a callback refers to an undefined function or if some arguments are missing.
 *
 * @package Eva\EvaEngine\Exception
 */
class BadFunctionCallException extends LogicException
{
    /**
     * @var int
     */
    protected $statusCode = 400;
}
