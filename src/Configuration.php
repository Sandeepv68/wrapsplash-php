<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP;

final readonly class Configuration
{
    /**
     * @throws WrapSplashException
     */
    public function __construct(
        public ?string $bearerToken = null,
        public ?string $accessToken = null,
        public ?string $secretKey = null,
        public ?string $redirectUri = null,
        public ?string $code = null,
        public int $timeout = 10000,
        public int $retries = 2,
        public int $retryDelayMs = 100,
    ) {
        if ($this->timeout <= 0) {
            throw new WrapSplashException('Timeout must be a positive integer (ms).');
        }
        if ($this->retries < 0) {
            throw new WrapSplashException('Retries must be a non-negative integer.');
        }
        if ($this->retryDelayMs < 0) {
            throw new WrapSplashException('Retry delay must be a non-negative integer (ms).');
        }
    }

    /**
     * @throws WrapSplashException
     */
    public function validate(): void
    {
        if ($this->bearerToken !== null) {
            return;
        }

        $missing = [];
        if ($this->accessToken === null || $this->accessToken === '') {
            $missing[] = 'access_token';
        }
        if ($this->secretKey === null || $this->secretKey === '') {
            $missing[] = 'secret_key';
        }
        if ($this->redirectUri === null || $this->redirectUri === '') {
            $missing[] = 'redirect_uri';
        }
        if ($this->code === null || $this->code === '') {
            $missing[] = 'code';
        }

        if ($missing !== []) {
            throw new WrapSplashException(
                'Missing required credentials: ' . implode(', ', $missing) . '. ' .
                'Provide a bearer_token or all of: access_token, secret_key, redirect_uri, code.'
            );
        }
    }
}
