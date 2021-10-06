<?php

namespace ProductStatus\EventListerner;

use ProductStatus\Model\Map\ProductProductStatusTableMap;
use ProductStatus\Model\Map\ProductStatusTableMap;
use ProductStatus\Model\ProductProductStatus;
use ProductStatus\Model\ProductStatus;
use ProductStatus\Model\ProductStatusQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Loop\LoopExtendsArgDefinitionsEvent;
use Thelia\Core\Event\Loop\LoopExtendsBuildModelCriteriaEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Model\Base\ProductQuery;
use Thelia\Model\Map\ProductTableMap;

class ProductLoopExtendListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::getLoopExtendsEvent(TheliaEvents::LOOP_EXTENDS_ARG_DEFINITIONS, 'product') => ['extendArgDefinition', 128],
            TheliaEvents::getLoopExtendsEvent(TheliaEvents::LOOP_EXTENDS_BUILD_MODEL_CRITERIA, 'product') => ['extendLoopModelCriteria', 128]

        ];
    }

    public function extendArgDefinition(LoopExtendsArgDefinitionsEvent $event)
    {
        $event->getArgumentCollection()
            ->addArgument(Argument::createAlphaNumStringListTypeArgument('product_status_code'))
            ->addArgument(Argument::createIntListTypeArgument('product_status_id'));
    }

    public function extendLoopModelCriteria(LoopExtendsBuildModelCriteriaEvent $event)
    {

        /** @var ProductQuery $search */
        $search = $event->getModelCriteria();
        if ($productStatusCodes = $event->getLoop()->getProductStatusCode()){
            $productStatuses = ProductStatusQuery::create()->filterByCode($productStatusCodes)->find();
            $this->getProductStatus($search, $productStatuses);
        }

        if ($productStatusIds = $event->getLoop()->getProductStatusId()){
            $productStatuses = ProductStatusQuery::create()->filterById($productStatusIds)->find();
            $this->getProductStatus($search, $productStatuses);
        }
    }

    protected function getProductStatus($search, $productStatuses)
    {
        $statusIds = [];
        $isNormal = false;
        foreach ($productStatuses as $productStatus){
            $statusIds[] = $productStatus->getId();

            if ($productStatus->getCode() === 'normal'){
                $isNormal = true;
            }
        }

        $productProductStatusJoin = new Join(
            ProductTableMap::COL_ID,
            ProductProductStatusTableMap::COL_PRODUCT_ID,
            Criteria::LEFT_JOIN
        );

        $search->addJoinObject($productProductStatusJoin, 'productProductStatusJoin');

        $search->where(ProductProductStatusTableMap::COL_PRODUCT_STATUS_ID. ' IN (' . implode(',', $statusIds) . ') ');
        if ($isNormal){
            $search
                ->_or()
                ->where(ProductProductStatusTableMap::COL_ID. ' IS NULL');
        }
    }
}