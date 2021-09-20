<?php

namespace ProductStatus\Loop;

use ProductStatus\Model\ProductProductStatus;
use ProductStatus\Model\ProductStatus;
use ProductStatus\Model\ProductStatusQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class ProductStatusLoop extends BaseI18nLoop implements PropelSearchLoopInterface
{
    protected $timestampable = true;

    protected function getArgDefinitions() : ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            Argument::createAnyTypeArgument('code'),
            Argument::createEnumListTypeArgument(
                'order',
                [
                    'alpha',
                    'alpha_reverse',
                    'id'
                ],
                'id'
            )
        );
    }

    public function buildModelCriteria()
    {
        $search = ProductStatusQuery::create();

        /* manage translations */
        $this->configureI18nProcessing($search);

        if (null !== $id = $this->getId()) {
            $search->filterById($id, Criteria::IN);
        }

        if (null !== $code = $this->getCode()) {
            $search->filterByCode($code, Criteria::EQUAL);
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
                case 'id':
                    $search->addAscendingOrderByColumn('id');
                    break;
            }
        }

        return $search;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var ProductStatus $productStatus */
        foreach ($loopResult->getResultDataCollection() as $productStatus) {
            $loopResultRow = new LoopResultRow($productStatus);

                $loopResultRow->set('LOCALE', $this->locale)
                    ->set('ID', $productStatus->getId())
                    ->set('CODE', $productStatus->getCode())
                    ->set('COLOR', $productStatus->getColor())
                    ->set('DESCRIPTION', $productStatus->getVirtualColumn('i18n_DESCRIPTION'))
                    ->set('PROTECTED', $productStatus->getProtected())
                    ->set('CREATED_AT', $productStatus->getCreatedAt())
                    ->set('UPDATED_AT', $productStatus->getUpdatedAt())
                    ->set('TITLE', $productStatus->getVirtualColumn('i18n_TITLE'));

                $this->addOutputFields($loopResultRow, $productStatus);

                $loopResult->addRow($loopResultRow);
            }

        return $loopResult;
    }
}