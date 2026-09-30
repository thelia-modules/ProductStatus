<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProductStatus\Service;

/**
 * A status color is written `#rrggbb` and printed inside a `style` attribute: anything
 * else is refused on write and replaced by a neutral grey on display.
 */
final class ProductStatusColor
{
    public const NEUTRAL = '#6c757d';

    private const PATTERN = '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i';

    public static function isValid(string $color): bool
    {
        return 1 === preg_match(self::PATTERN, $color);
    }

    /**
     * Lower-cased `#rrggbb` (a `#rgb` is expanded), or null when the color is not valid.
     */
    public static function normalize(string $color): ?string
    {
        $color = strtolower(trim($color));

        if (!self::isValid($color)) {
            return null;
        }

        if (4 === \strlen($color)) {
            return '#'.$color[1].$color[1].$color[2].$color[2].$color[3].$color[3];
        }

        return $color;
    }

    public static function forDisplay(?string $color): string
    {
        return self::normalize((string) $color) ?? self::NEUTRAL;
    }
}
