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

namespace ProductStatus\Exception;

final class UnknownProductStatusException extends \InvalidArgumentException implements ProductStatusErrorInterface
{
    public function __construct(?int $statusId)
    {
        parent::__construct(\sprintf('Unknown product status %d.', (int) $statusId));
    }

    public function translationKey(): string
    {
        return 'This status does not exist';
    }
}
