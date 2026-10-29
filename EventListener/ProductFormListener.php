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

use ProductStatus\Exception\ProductStatusErrorInterface;
use ProductStatus\ProductStatus;
use ProductStatus\Service\ProductStatusService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Security\SecurityContext;

/**
 * The status select of the product form, in the General tab of the default-twig back-office
 * (rendered by HookManager::onProductModificationForm()), saved with the product.
 *
 * The field is added for an administrator allowed to update the module only, and the permission
 * is checked again before writing. The field reaches the update event through the legacy form
 * bridge of the back-office, which sets the fields without a setter on the event; an update
 * coming from anywhere else leaves it unset. The status shown unchanged writes nothing: a product
 * without status keeps reading `normal` without a link.
 *
 * The form refuses a status deleted since the page was displayed, product included. One deleted
 * between that validation and the write, once the core has committed the product, leaves a
 * warning, not the failure of the save.
 */
final readonly class ProductFormListener implements EventSubscriberInterface
{
    public const string FIELD = 'product_status_id';

    public const string PRODUCT_FORM = 'thelia_product_modification';

    /** Below the core update (Thelia\Action\Product, 128) and the catalogue price rules (64): saved once the product is. */
    private const int AFTER_CORE_UPDATE = 32;

    public function __construct(
        private ProductStatusService $productStatusService,
        private SecurityContext $securityContext,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD.'.'.self::PRODUCT_FORM => 'addField',
            TheliaEvents::PRODUCT_UPDATE => ['saveStatus', self::AFTER_CORE_UPDATE],
        ];
    }

    public function canUpdate(): bool
    {
        return $this->securityContext->isGranted(['ADMIN'], [AdminResources::MODULE], [ProductStatus::getModuleCode()], [AccessManager::UPDATE]);
    }

    public function addField(TheliaFormEvent $event): void
    {
        if (!$this->canUpdate()) {
            return;
        }

        // Only the ids are validated: the template renders the select with the titles of the edit language.
        $event->getForm()->getFormBuilder()->add(self::FIELD, ChoiceType::class, [
            'required' => false,
            'choices' => $this->productStatusService->ids(),
        ]);
    }

    public function saveStatus(ProductUpdateEvent $event): void
    {
        if (!isset($event->{self::FIELD}) || null === $event->getProduct() || !$this->canUpdate()) {
            return;
        }

        $productId = (int) $event->getProductId();
        $statusId = (int) $event->{self::FIELD};

        if ($statusId === $this->productStatusService->statusOf($productId, (string) $event->getLocale())->id) {
            return;
        }

        try {
            $this->productStatusService->assign($productId, $statusId);
        } catch (ProductStatusErrorInterface $exception) {
            $this->warn($exception);
        }
    }

    private function warn(ProductStatusErrorInterface $exception): void
    {
        $request = $this->requestStack->getMainRequest();
        $session = null !== $request && $request->hasSession() ? $request->getSession() : null;

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add('warning', $this->translator->trans(
            'The product was saved, but not its status: %reason',
            ['%reason' => $this->translator->trans($exception->translationKey(), [], ProductStatus::DOMAIN_NAME)],
            ProductStatus::DOMAIN_NAME,
        ));
    }
}
