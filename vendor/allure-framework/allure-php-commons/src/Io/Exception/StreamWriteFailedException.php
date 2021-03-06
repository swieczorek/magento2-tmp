<?php

declare(strict_types=1);

namespace Qameta\Allure\Io\Exception;

use RuntimeException;
use Throwable;

final class StreamWriteFailedException extends RuntimeException
{
    public function __construct(?string $message = null, Throwable $previous = null)
    {
        parent::__construct($this->buildMessage($message), 0, $previous);
    }

    private function buildMessage(?string $message): string
    {
        $baseMessage = "Failed to write data in stream";

        return isset($message)
            ? "{$baseMessage}: {$message}"
            : $baseMessage;
    }
}
