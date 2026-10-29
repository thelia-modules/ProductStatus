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

namespace ProductStatus\EventListener;

use ProductStatus\ProductStatus;
use ProductStatus\Service\ProductStatusService;
use ProductStatus\Service\ProductStatusView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\SecurityContext;
use Thelia\Model\Lang;

/**
 * The status select of the product form, in the General tab of the default-twig back-office
 * (rendered by HookManager::onProductModificationForm()), saved with the product.
 *
 * The field is added for an administrator allowed to update the module only. It reaches the
 * update event through the legacy form bridge of the back-office, which sets the fields without
 * a setter on the event; an update coming from anywhere else leaves it unset. The status shown
 * unchanged writes nothing: a product without status keeps reading `normal` without a link.
 */
final readonly class ProductFormListener implements EventSubscriberInterface
{
    public const string FIELD = 'product_status_id';

    public const string PRODUCT_FORM = 'thelia_product_modification';

    /** Below the core update (Thelia\Action\Product, 128): saved once the product is. */
    private const int AFTER_CORE_UPDATE = 64;

    public function __construct(
        private ProductStatusService $productStatusService,
        private SecurityContext $securityContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD.'.'.self::PRODUCT_FORM => 'addField',
            TheliaEvents::PRODUCT_UPDATE => ['saveStatus', self::AFTER_CORE_UPDATE],
        ];
    }

    public function addField(TheliaFormEvent $event): void
    {
        if (!$this->securityContext->isGranted(['ADMIN'], [AdminResources::MODULE], [ProductStatus::getModuleCode()], [AccessManager::UPDATE])) {
            return;
        }

        // Only the ids are validated: the template renders the select with the titles of the edit language.
        $event->getForm()->getFormBuilder()->add(self::FIELD, ChoiceType::class, [
            'required' => false,
            'choices' => array_map(
                static fn (ProductStatusView $status): int => $status->id,
                $this->productStatusService->all($this->locale()),
            ),
        ]);
    }

    public function saveStatus(ProductUpdateEvent $event): void
    {
        if (!isset($event->{self::FIELD})) {
            return;
        }

        $productId = (int) $event->getProductId();
        $statusId = (int) $event->{self::FIELD};

        if ($statusId === $this->productStatusService->statusOf($productId, $this->locale())->id) {
            return;
        }

        $this->productStatusService->assign($productId, $statusId);
    }

    private function locale(): string
    {
        return Lang::getDefaultLanguage()->getLocale() ?? 'en_US';
    }
}
