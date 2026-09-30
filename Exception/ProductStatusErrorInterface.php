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

/**
 * An error of the module that can be shown to an administrator: its translation key
 * lives in the `productstatus` domain.
 */
interface ProductStatusErrorInterface extends \Throwable
{
    public function translationKey(): string;
}
