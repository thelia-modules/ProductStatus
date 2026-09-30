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

namespace ProductStatus\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ProductStatusChangedEvent extends Event
{
    public function __construct(
        public readonly int $productId,
        public readonly ?int $previousStatusId,
        public readonly int $statusId,
    ) {
    }
}
