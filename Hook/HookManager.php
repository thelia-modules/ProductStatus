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

namespace ProductStatus\Hook;

use ProductStatus\Form\ProductStatusAssignForm;
use ProductStatus\Form\StatusForm;
use ProductStatus\Service\EditionLocaleResolver;
use ProductStatus\Service\ProductStatusService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Tools\TokenProvider;

class HookManager extends BaseHook
{
    public function __construct(
        private readonly ProductStatusService $productStatusService,
        private readonly TheliaFormFactory $formFactory,
        private readonly TokenProvider $tokenProvider,
        private readonly EditionLocaleResolver $editionLocaleResolver,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    /**
     * @return array<string, list<array{type: string, method: string}>>
     */
    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
            'product.tab-content' => [
                ['type' => 'back', 'method' => 'onProductTabContent'],
            ],
            'product-edit.top' => [
                ['type' => 'back', 'method' => 'onProductEditTop'],
            ],
        ];
    }

    /**
     * The list shows each status with its fallback (default language, then code); the edit
     * forms are filled with what is stored in the edited language only, so saving a form
     * never copies the fallback text into that language.
     */
    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $editionLang = $this->editionLocaleResolver->resolveLang($this->getRequest());
        $locale = $this->editionLocaleResolver->resolveLocale($this->getRequest());
        $statuses = $this->productStatusService->all($locale);
        $translations = $this->productStatusService->translations($locale);

        $editForms = [];
        foreach ($statuses as $status) {
            $editForms[$status->id] = $this->withIdPrefix(
                $this->formFactory->createForm(StatusForm::class, data: [
                    'code' => $status->code,
                    'title' => $translations[$status->id]['title'] ?? '',
                    'description' => $translations[$status->id]['description'] ?? '',
                    'color' => $status->color,
                ])->createView()->getView(),
                StatusForm::getName().'_'.$status->id,
            );
        }

        $event->add($this->render('ProductStatus/module_configuration.html.twig', [
            'statuses' => $statuses,
            'create_form' => $this->formFactory->createForm(StatusForm::class)->createView()->getView(),
            'edit_forms' => $editForms,
            'delete_token' => $this->tokenProvider->assignToken(),
            'edit_language_id' => $editionLang->getId(),
        ]));
    }

    public function onProductTabContent(HookRenderEvent $event): void
    {
        $productId = (int) $event->getArgument('product');
        $currentStatus = $this->productStatusService->statusOf($productId, $this->editionLocaleResolver->resolveLocale($this->getRequest()));

        $assignForm = $this->formFactory->createForm(ProductStatusAssignForm::class, data: [
            'product_status_id' => $currentStatus->id,
        ]);

        $event->add($this->render('ProductStatus/product_tab_content.html.twig', [
            'product_id' => $productId,
            'current_status' => $currentStatus,
            'assign_form' => $assignForm->createView()->getView(),
        ]));
    }

    public function onProductEditTop(HookRenderEvent $event): void
    {
        $productId = (int) $event->getArgument('product_id');

        if (0 === $productId) {
            return;
        }

        $currentStatus = $this->productStatusService->statusOf($productId, $this->editionLocaleResolver->resolveLocale($this->getRequest()));

        $event->add($this->render('ProductStatus/product_edit_top.html.twig', [
            'current_status' => $currentStatus,
        ]));
    }

    /**
     * The creation form and every edit form share the form name the controller reads, so
     * their HTML ids are made unique here; field names are left untouched.
     */
    private function withIdPrefix(FormView $view, string $id): FormView
    {
        $view->vars['id'] = $id;

        foreach ($view->children as $name => $child) {
            $this->withIdPrefix($child, $id.'_'.$name);
        }

        return $view;
    }
}
