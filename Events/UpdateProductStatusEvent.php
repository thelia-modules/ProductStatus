<?php

namespace ProductStatus\Events;

use ProductStatus\Model\ProductProductStatus;
use Thelia\Core\Event\ActionEvent;
use Thelia\Model\Product;

class UpdateProductStatusEvent extends ActionEvent
{
    const PRODUCT_STATUS_UPDATE = 'action.product.status.update';

    /** @var ProductProductStatus */
    protected $productProductStatus;

    /** @var Product */
    protected $product;

    public function getProduct(): Product
    {
       return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @param ProductProductStatus $productProductStatus
     */
    public function setProductProductStatus(ProductProductStatus $productProductStatus)
    {
        $this->productProductStatus = $productProductStatus;
    }

    public function getProductStatusId(): int
    {
        return $this->productProductStatus->getProductStatusId();
    }

    public function getProductId(): int
    {
        return $this->productProductStatus->getProductId();
    }

    public function getCode(): string
    {
        return $this->productProductStatus->getProductStatus()->getCode();
    }

    public function getTitle(): string
    {
        return $this->productProductStatus->getProductStatus()->getTitle();
    }

    public function getProtected(): int
    {
        return $this->productProductStatus->getProductStatus()->getProtected();
    }
}