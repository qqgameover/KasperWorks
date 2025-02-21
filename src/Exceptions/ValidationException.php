<?php namespace Kasperworks\Exceptions;

class ValidationException extends \Exception
{
    public function __construct(
        string $message,
        protected array $errors,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
