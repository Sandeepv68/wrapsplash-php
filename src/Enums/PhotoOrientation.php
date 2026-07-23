<?php

declare(strict_types=1);

namespace SandeepV\WrapsplashPHP\Enums;

enum PhotoOrientation: string
{
    case LANDSCAPE = 'landscape';
    case PORTRAIT = 'portrait';
    case SQUARE = 'squarish';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
