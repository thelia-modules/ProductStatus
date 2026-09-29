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

namespace ProductStatus\Hook\Theme;

use ProductStatus\Service\ProductStatusCode;
use ProductStatus\Service\ProductStatusColor;
use ProductStatus\Service\ProductStatusMemo;
use ProductStatus\Service\ProductStatusReader;
use Thelia\Core\Hook\Theme\ThemeHookInterface;
use Twig\Environment;

/**
 * Prints the status of the product at the bottom of the product page, as the 2.x line
 * did on the `product.bottom` hook: nothing for the `normal` status, the title and the
 * description in the language of the shop otherwise.
 *
 * The status comes from the product resource the theme passes (the `ProductStatusAddon`
 * of `/api/front/products/{id}`); a product given without it costs one query.
 */
final readonly class ProductStatusThemeHook implements ThemeHookInterface
{
    public const HOOK_NAME = 'product.bottom';

    public function __construct(
        private Environment $twig,
    ) {
    }

    public function supports(string $hookName): bool
    {
        return self::HOOK_NAME === $hookName;
    }

    public function render(string $hookName, array $parameters): string
    {
        $status = $this->statusOf($parameters['product'] ?? null);

        if (null === $status || ProductStatusReader::DEFAULT_STATUS_CODE === $status['code']) {
            return '';
        }

        return $this->twig->render('@ProductStatusModule/theme-hook/product_status.html.twig', [
            'status' => $status,
            'css_modifier' => ProductStatusCode::cssModifier($status['code']),
        ]);
    }

    /**
     * @return array{code: string, title: string, description: ?string, color: string}|null
     */
    private function statusOf(mixed $product): ?array
    {
        $exposedStatus = \is_array($product) ? ($product['ProductStatusAddon']['productStatus'] ?? null) : null;

        if (\is_array($exposedStatus) && isset($exposedStatus['code'], $exposedStatus['title'], $exposedStatus['color'])) {
            return [
                'code' => (string) $exposedStatus['code'],
                'title' => (string) $exposedStatus['title'],
                'description' => isset($exposedStatus['description']) ? (string) $exposedStatus['description'] : null,
                'color' => ProductStatusColor::forDisplay((string) $exposedStatus['color']),
            ];
        }

        $productId = $this->productIdFrom($product);

        if (null === $productId) {
            return null;
        }

        $view = ProductStatusMemo::viewFor($productId, null, false);

        return [
            'code' => $view->code,
            'title' => $view->title,
            'description' => $view->description,
            'color' => $view->color,
        ];
    }

    private function productIdFrom(mixed $product): ?int
    {
        $productId = match (true) {
            \is_array($product) => $product['id'] ?? null,
            \is_object($product) && method_exists($product, 'getId') => $product->getId(),
            default => $product,
        };

        return is_numeric($productId) && (int) $productId > 0 ? (int) $productId : null;
    }
}
