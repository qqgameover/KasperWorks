<?php namespace Kasperworks\Exceptions;

class RowNotFoundException extends \Exception
{
    public function __construct(
        $message = "The ID did not have a corrosponding row in the given table",
        $code = 404,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
