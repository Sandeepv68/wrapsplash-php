<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP;

class WrapSplashException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?string $statusText = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
