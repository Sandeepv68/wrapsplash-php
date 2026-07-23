<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP\Enums;

enum PhotoOrder: string
{
    case LATEST = 'latest';
    case OLDEST = 'oldest';
    case POPULAR = 'popular';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
