<?php

namespace App\Exceptions\Http;

use App\Core\Formatter\ExceptionMessage\ExceptionMessage;
use RuntimeException;
use Throwable;

abstract class AbstractHttpException extends RuntimeException
{
    public function __construct(
        protected ExceptionMessage $exceptionMessage,
        ?Throwable $previousException = null
    ) {
        parent::__construct(
            $this->exceptionMessage->getMessage(),
            $this->getStatusCode(),
            $previousException
        );
    }

    public function render($request)
    {
        return response()->json(
            $this->exceptionMessage->getJsonResponse(),
            $this->getStatusCode()
        );
    }

    abstract protected function getStatusCode(): int;
}
