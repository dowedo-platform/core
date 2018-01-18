<?php
namespace Dowedo\Core\Exception;


class InvalidApiKeyException extends RuntimeException
{
    /**
     * @var int
     */
    protected $statusCode = 403;
}

// EOF
