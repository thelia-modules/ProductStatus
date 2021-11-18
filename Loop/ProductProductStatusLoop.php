<?php

namespace ProductStatus\Loop;

use ProductStatus\Model\ProductProductStatus;
use ProductStatus\Model\ProductStatusQuery;
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
        $this->configureI18nProcessing($search, ['TITLE', 'CHAPO', 'DESCRIPTION', 'POSTSCRIPTUM', 'BACKOFFICE_TITLE']);

        $currentProductId = $this->getArgumentCollection()->get('product_id');

        if (!$currentProductId)
        {
            return;
        }

        $search->useProductProductStatusQuery()
            ->filterByProductId($currentProductId->getValue())
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
        return $search;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var ProductProductStatus $productStatus */
        foreach ($loopResult->getResultDataCollection() as $productStatus) {
            $loopResultRow = new LoopResultRow($productStatus);

            $loopResultRow->set('LOCALE', $this->locale)
                ->set('STATUS_ID', $productStatus->getId())
                ->set('STATUS_COLOR', $productStatus->getColor())
                ->set('STATUS_DESCRIPTION', $productStatus->getVirtualColumn('i18n_DESCRIPTION'))
                ->set('STATUS_BO_TITLE', $productStatus->getVirtualColumn('i18n_BACKOFFICE_TITLE'))
                ->set('STATUS_TITLE', $productStatus->getVirtualColumn('i18n_TITLE'));
            $this->addOutputFields($loopResultRow, $productStatus);

            $loopResult->addRow($loopResultRow);

        }

        return $loopResult;
    }
}