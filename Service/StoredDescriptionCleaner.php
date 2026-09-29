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

use ProductStatus\Model\ProductStatusI18nQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionInterface;

/**
 * Runs {@see ProductStatusDescriptionSanitizer} over every stored description, for the
 * descriptions written by the 2.x line, which kept any HTML. Cleaning an already clean
 * description changes nothing, so it can run more than once.
 */
final readonly class StoredDescriptionCleaner
{
    public function __construct(
        private ProductStatusDescriptionSanitizer $sanitizer = new ProductStatusDescriptionSanitizer(),
    ) {
    }

    /**
     * @return int the number of translations rewritten
     */
    public function cleanAll(?ConnectionInterface $connection = null): int
    {
        $rewritten = 0;

        $translations = ProductStatusI18nQuery::create()->filterByDescription(null, Criteria::ISNOTNULL)->find($connection);
        foreach ($translations as $translation) {
            $description = (string) $translation->getDescription();
            $sanitized = $this->sanitizer->sanitize($description);

            if ($sanitized === $description) {
                continue;
            }

            $translation->setDescription('' === $sanitized ? null : $sanitized)->save($connection);
            ++$rewritten;
        }

        return $rewritten;
    }
}
