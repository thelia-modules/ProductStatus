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

namespace ProductStatus\Tests;

use ProductStatus\Model\ProductProductStatusQuery;
use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\ProductStatus;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Model\Product;
use Thelia\Test\IntegrationTestCase;

/**
 * Plays the one-status-per-product script on a link table shaped as the 2.x line left it
 * (a plain index on product_id, duplicate links). DDL commits implicitly: this test runs
 * outside any transaction and puts the table and its rows back itself.
 *
 * Runs on the test database of the project (`php bin/test-prepare`), never on the shop's.
 */
final class UniqueLinkScriptTest extends IntegrationTestCase
{
    private const UNIQUE_INDEX = 'product_product_status_product_id_unique';

    private const LEGACY_INDEX = 'fi_product';

    protected bool $useTransaction = false;

    /** @var list<ActiveRecordInterface> rows created by the test, deleted in this order */
    private array $createdRows = [];

    protected function setUp(): void
    {
        $databaseName = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($databaseName) || !str_ends_with($databaseName, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $databaseName));
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $connection = $this->getPropelConnection();

        foreach ($this->createdRows as $row) {
            $row->delete($connection);
        }
        $this->createdRows = [];

        if (!$this->indexExists(self::UNIQUE_INDEX)) {
            $connection->exec('DELETE duplicate FROM product_product_status duplicate INNER JOIN product_product_status kept ON kept.product_id = duplicate.product_id AND kept.id < duplicate.id');
            $connection->exec('ALTER TABLE product_product_status ADD UNIQUE INDEX '.self::UNIQUE_INDEX.' (product_id)');
        }
        if ($this->indexExists(self::LEGACY_INDEX)) {
            $connection->exec('ALTER TABLE product_product_status DROP INDEX '.self::LEGACY_INDEX);
        }

        parent::tearDown();
    }

    public function testActivationRemovesDuplicateLinksKeepingTheOldestThenAddsTheUniqueIndex(): void
    {
        $connection = $this->getPropelConnection();
        $connection->exec('ALTER TABLE product_product_status ADD INDEX '.self::LEGACY_INDEX.' (product_id)');
        $connection->exec('ALTER TABLE product_product_status DROP INDEX '.self::UNIQUE_INDEX);

        $fixtures = $this->createFixtureFactory();
        $category = $fixtures->category();
        $taxRule = $fixtures->taxRule();
        $currency = $fixtures->currency();
        $withDuplicates = $fixtures->product($category, $taxRule, $currency);
        $withOneLink = $fixtures->product($category, $taxRule, $currency);
        // The tax rule and the currency are rows the database already had: the factory reuses them.
        $this->createdRows = [$withDuplicates, $withOneLink, $category];

        $this->link($connection, $withDuplicates, 'sale');
        $this->link($connection, $withDuplicates, 'oddment');
        $this->link($connection, $withDuplicates, 'discontinued');
        $this->link($connection, $withOneLink, 'oddment');

        (new ProductStatus())->postActivation($connection);
        (new ProductStatus())->postActivation($connection);

        self::assertTrue($this->indexExists(self::UNIQUE_INDEX));
        self::assertSame([$this->statusId('sale')], $this->linkedStatusIds($withDuplicates));
        self::assertSame([$this->statusId('oddment')], $this->linkedStatusIds($withOneLink));
    }

    private function link(ConnectionInterface $connection, Product $product, string $statusCode): void
    {
        $statement = $connection->prepare('INSERT INTO product_product_status (product_id, product_status_id, created_at, updated_at) VALUES (:product, :status, NOW(), NOW())');
        $statement->execute([':product' => $product->getId(), ':status' => $this->statusId($statusCode)]);
    }

    /**
     * @return list<int>
     */
    private function linkedStatusIds(Product $product): array
    {
        return array_map(
            static fn (mixed $statusId): int => (int) $statusId,
            ProductProductStatusQuery::create()->filterByProductId($product->getId())->orderById()->select(['ProductStatusId'])->find()->getData(),
        );
    }

    private function statusId(string $code): int
    {
        return (int) ProductStatusQuery::create()->findOneByCode($code)?->getId();
    }

    private function indexExists(string $indexName): bool
    {
        $statement = $this->getPropelConnection()->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = \'product_product_status\' AND index_name = :index');
        $statement->execute([':index' => $indexName]);

        return (int) $statement->fetchColumn() > 0;
    }
}
