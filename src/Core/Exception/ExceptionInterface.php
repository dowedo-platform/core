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
 * Basic Exception Interface, defined http status code as a part of exception
 * @package Carter\Core\Exception
 */
interface ExceptionInterface
{
    /**
     * @return int
     */
    public function getStatusCode();
}
