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
 * Domain Exception
 *
 * Exception thrown if a value does not adhere to a defined valid data domain.
 *
 * @package Carter\Core\Exception
 */
class DomainException extends LogicException
{
    /**
     * @var int
     */
    protected $statusCode = 400;
}
