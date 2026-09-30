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

namespace ProductStatus\Service;

/**
 * Statuses loaded in one locale, and the single rule giving the status of a product:
 * its linked status when known, the protected `normal` status otherwise.
 */
final readonly class ProductStatusViewSet
{
    /**
     * @param array<int, ProductStatusView> $byId
     */
    public function __construct(
        public array $byId,
        public ?ProductStatusView $default,
    ) {
    }

    public function viewForStatusId(?int $statusId): ProductStatusView
    {
        if (null !== $statusId && isset($this->byId[$statusId])) {
            return $this->byId[$statusId];
        }

        return $this->default
            ?? throw new \LogicException(\sprintf('The default product status "%s" is missing.', ProductStatusReader::DEFAULT_STATUS_CODE));
    }
}
