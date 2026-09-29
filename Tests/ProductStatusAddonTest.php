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

use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\Service\ProductStatusService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Test\IntegrationTestCase;

/**
 * Runs on the test database of the project (`php bin/test-prepare`), never on the shop's.
 * The request goes through the kernel itself: symfony/browser-kit is not required by the module.
 */
final class ProductStatusAddonTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $databaseName = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($databaseName) || !str_ends_with($databaseName, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $databaseName));
        }

        parent::setUp();
    }

    public function testFrontProductExposesItsStatusWithoutTheProtectedFlag(): void
    {
        $fixtures = $this->createFixtureFactory();
        $category = $fixtures->category();
        $taxRule = $fixtures->taxRule();
        $currency = $fixtures->currency();
        $withStatus = $fixtures->product($category, $taxRule, $currency);
        $withoutStatus = $fixtures->product($category, $taxRule, $currency);

        $dispatcher = static::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        (new ProductStatusService($dispatcher))->assign(
            $withStatus->getId(),
            (int) ProductStatusQuery::create()->findOneByCode('oddment')?->getId(),
        );

        $withStatusBody = $this->frontProduct($withStatus->getId());
        $withoutStatusBody = $this->frontProduct($withoutStatus->getId());

        self::assertSame(['code', 'title', 'description', 'color'], array_keys($withStatusBody['ProductStatusAddon']['productStatus']));
        self::assertSame('oddment', $withStatusBody['ProductStatusAddon']['productStatus']['code']);
        self::assertSame('normal', $withoutStatusBody['ProductStatusAddon']['productStatus']['code']);
    }

    /**
     * @return array<string, mixed>
     */
    private function frontProduct(int $productId): array
    {
        $request = Request::create('/api/front/products/'.$productId, server: ['HTTP_ACCEPT' => 'application/json']);
        $response = self::$kernel->handle($request);
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $body = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        return $body;
    }
}
