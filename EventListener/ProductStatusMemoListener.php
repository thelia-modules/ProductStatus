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

use ProductStatus\Service\ProductStatusMemo;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Domain\Localization\Service\LangService;

final readonly class ProductStatusMemoListener implements EventSubscriberInterface
{
    public function __construct(
        private LangService $langService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 256],
            ConsoleEvents::COMMAND => ['onCommand', 256],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $langService = $this->langService;
        ProductStatusMemo::reset(static fn (): ?string => $langService->getLang()?->getLocale());
    }

    public function onCommand(): void
    {
        ProductStatusMemo::reset();
    }
}
