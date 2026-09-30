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

namespace ProductStatus\Form;

use ProductStatus\Service\EditionLocaleResolver;
use ProductStatus\Service\ProductStatusService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Thelia\Form\BaseForm;

/**
 * The status assignment select rendered in the product "Modules" tab, labelled in the
 * back-office edit language ({@see EditionLocaleResolver}).
 */
class ProductStatusAssignForm extends BaseForm
{
    public function __construct(
        private readonly ProductStatusService $productStatusService,
        private readonly EditionLocaleResolver $editionLocaleResolver,
    ) {
    }

    protected function buildForm(): void
    {
        // Keyed by label, valued by id and ordered by id (ProductStatusService::all()):
        // the code in the label keeps apart several statuses sharing the same title.
        $choices = [];
        foreach ($this->productStatusService->all($this->editionLocaleResolver->resolveLocale($this->request)) as $status) {
            $choices[\sprintf('%s (%s)', $status->title, $status->code)] = $status->id;
        }

        $this->formBuilder->add('product_status_id', ChoiceType::class, [
            'required' => true,
            'label' => false,
            'placeholder' => false,
            'choices' => $choices,
        ]);
    }

    public static function getName(): string
    {
        return 'productstatus_assign';
    }
}
