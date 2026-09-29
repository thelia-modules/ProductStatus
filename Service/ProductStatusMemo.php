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

use Thelia\Model\Lang;

/**
 * Product statuses read once per request, for the API resource addon.
 *
 * The Propel bridge builds an addon with `new`, so it cannot receive a service: the
 * statuses (a handful of rows) are loaded in one query per request and locale, and the
 * status of each product comes from the column the addon joins on the product query.
 * A product reached without that column (through another resource) costs one query,
 * remembered for the rest of the request.
 *
 * Emptied at every request and console command ({@see \ProductStatus\EventListener\ProductStatusMemoListener})
 * and after every write of {@see ProductStatusService}.
 */
final class ProductStatusMemo
{
    /** @var array<string, ProductStatusViewSet> */
    private static array $viewSetByLocale = [];

    /** @var array<int, int|null> */
    private static array $statusIdByProductId = [];

    /** @var (\Closure(): ?string)|null */
    private static ?\Closure $localeResolver = null;

    /**
     * @param (\Closure(): ?string)|null $localeResolver
     */
    public static function reset(?\Closure $localeResolver = null): void
    {
        self::clear();
        self::$localeResolver = $localeResolver;
    }

    public static function clear(): void
    {
        self::$viewSetByLocale = [];
        self::$statusIdByProductId = [];
    }

    public static function currentLocale(): string
    {
        $locale = null === self::$localeResolver ? null : (self::$localeResolver)();

        return $locale ?? Lang::getDefaultLanguage()->getLocale();
    }

    /**
     * @param bool $statusIdKnown true when $statusId was read with the product (null then means "no link")
     */
    public static function viewFor(int $productId, ?int $statusId, bool $statusIdKnown, ?string $locale = null): ProductStatusView
    {
        $locale ??= self::currentLocale();

        if (!$statusIdKnown) {
            if (!\array_key_exists($productId, self::$statusIdByProductId)) {
                self::$statusIdByProductId[$productId] = (new ProductStatusReader())->linkedStatusIds([$productId])[$productId] ?? null;
            }
            $statusId = self::$statusIdByProductId[$productId];
        }

        self::$viewSetByLocale[$locale] ??= (new ProductStatusReader())->loadViews($locale, null, includeDefault: false);

        return self::$viewSetByLocale[$locale]->viewForStatusId($statusId);
    }
}
