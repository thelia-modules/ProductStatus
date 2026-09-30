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

namespace ProductStatus\Api\Resource\Addon;

use ApiPlatform\Metadata\Operation;
use ProductStatus\Model\Map\ProductProductStatusTableMap;
use ProductStatus\Service\ProductStatusMemo;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\Serializer\Attribute\Groups;
use Thelia\Api\Resource\Product;
use Thelia\Api\Resource\PropelResourceInterface;
use Thelia\Api\Resource\ResourceAddonInterface;
use Thelia\Api\Resource\ResourceAddonTrait;
use Thelia\Model\Map\ProductTableMap;
use Thelia\Model\Product as ProductModel;

/**
 * Exposes the product status on `/api/front/products` (item and collection):
 * `{code, title, description, color}` in the current language, `normal` when the
 * product has none. Neither the protected flag nor the dates are exposed.
 *
 * The status id is left-joined on the product query (one row per product, the link
 * is unique), and the statuses themselves are read once per request.
 */
class ProductStatusAddon implements ResourceAddonInterface
{
    use ResourceAddonTrait;

    private const STATUS_ID_COLUMN = 'ProductStatusAddon_productStatusId';

    /**
     * @var array{code: string, title: string, description: ?string, color: string}|null
     */
    #[Groups([
        Product::GROUP_FRONT_READ,
        Product::GROUP_FRONT_READ_SINGLE,
    ])]
    public ?array $productStatus = null;

    public static function getResourceParent(): string
    {
        return Product::class;
    }

    public static function getPropelRelatedTableMap(): ?TableMap
    {
        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function extendQuery(ModelCriteria $query, ?Operation $operation = null, array $context = []): void
    {
        $query
            ->addJoin(ProductTableMap::COL_ID, ProductProductStatusTableMap::COL_PRODUCT_ID, Criteria::LEFT_JOIN)
            ->withColumn(ProductProductStatusTableMap::COL_PRODUCT_STATUS_ID, self::STATUS_ID_COLUMN);
    }

    public function buildFromModel(ActiveRecordInterface $activeRecord, PropelResourceInterface $abstractPropelResource): ResourceAddonInterface
    {
        if (!$activeRecord instanceof ProductModel || null === $activeRecord->getId()) {
            return $this;
        }
        $productId = $activeRecord->getId();

        $statusIdKnown = $activeRecord->hasVirtualColumn(self::STATUS_ID_COLUMN);
        $statusId = $statusIdKnown ? $activeRecord->getVirtualColumn(self::STATUS_ID_COLUMN) : null;

        $view = ProductStatusMemo::viewFor(
            $productId,
            is_numeric($statusId) ? (int) $statusId : null,
            $statusIdKnown,
        );

        $this->productStatus = [
            'code' => $view->code,
            'title' => $view->title,
            'description' => $view->description,
            'color' => $view->color,
        ];

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function buildFromArray(array $data, PropelResourceInterface $abstractPropelResource): ResourceAddonInterface
    {
        return $this;
    }

    public function doSave(ActiveRecordInterface $activeRecord, PropelResourceInterface $abstractPropelResource): void
    {
        // Read-only addon: statuses are written through ProductStatusService::assign().
    }

    public function doDelete(ActiveRecordInterface $activeRecord, PropelResourceInterface $abstractPropelResource): void
    {
        // Read-only addon: the link is removed by the foreign key cascade on product.
    }
}
