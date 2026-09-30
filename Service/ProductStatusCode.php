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
 * A status code is a slug (`a-z`, `0-9`, `-`, `_`). Codes written before 3.0.0 may hold
 * spaces or accents: they stay valid as long as they are not changed, and templates use
 * {@see self::cssModifier()} rather than the raw code.
 */
final class ProductStatusCode
{
    private const PATTERN = '/^[a-z0-9_-]+$/';

    public static function isValid(string $code): bool
    {
        return 1 === preg_match(self::PATTERN, $code);
    }

    /**
     * The code reduced to a slug, usable in a CSS class name: every run of other characters
     * becomes a single dash.
     */
    public static function cssModifier(string $code): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/', '-', mb_strtolower($code)), '-');
    }
}
