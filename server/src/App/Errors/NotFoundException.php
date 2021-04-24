<?php
namespace Robert2\API\Errors;

class NotFoundException extends \Exception
{
    public function __construct(string $message = null)
    {
        $message = $message ?: "Not Found.";
        parent::__construct($message, ERROR_NOT_FOUND, null);
    }
}
