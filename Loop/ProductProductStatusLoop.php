<?php

namespace ProductStatus\Loop;

use ProductStatus\Model\ProductProductStatus;
use ProductStatus\Model\ProductProductStatusQuery;
use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\ProductStatus;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class ProductProductStatusLoop extends BaseI18nLoop implements PropelSearchLoopInterface
{
    protected $timestampable = true;

    protected function getArgDefinitions() : ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            Argument::createIntListTypeArgument('product_status_id'),
            Argument::createEnumListTypeArgument(
                'order',
                [
                    'alpha',
                    'alpha_reverse',
                ],
                'alpha'
            )
        );
    }

    public function buildModelCriteria()
    {
        $search = ProductStatusQuery::create();

        /* manage translations */
        $this->configureI18nProcessing($search);

        $productId = $this->getCurrentRequest()->query->get('product_id');

        $search->useProductProductStatusQuery()
            ->filterByProductId($productId)
            ->endUse();

        if (null !== $id = $this->getId()) {
            $search->filterById($id, Criteria::IN);
        }

        if (null !== $productStatusId = $this->getProductStatusId()) {
            $search->filterByProductStatusId($productStatusId, Criteria::EQUAL);
        }

        $orders = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case 'alpha':
                    $search->addAscendingOrderByColumn('i18n_TITLE');
                    break;
                case 'alpha_reverse':
                    $search->addDescendingOrderByColumn('i18n_TITLE');
                    break;
            }
        }
//        dump($search->find());
        return $search;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var ProductStatus $productStatus */
        foreach ($loopResult->getResultDataCollection() as $productStatus) {
            $loopResultRow = new LoopResultRow($productStatus);

            $loopResultRow->set('LOCALE', $this->locale)
                ->set('ID', $productStatus->getId())
                ->set('COLOR', $productStatus->getColor())
                ->set('STATUS_TITLE', $productStatus->getVirtualColumn('i18n_TITLE'));

            $this->addOutputFields($loopResultRow, $productStatus);

            $loopResult->addRow($loopResultRow);

        }
//        dump($loopResult);

        return $loopResult;
    }
}