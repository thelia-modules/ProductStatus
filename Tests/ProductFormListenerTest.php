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

use BackOfficeDefaultTwigBundle\Form\Product\ProductType;
use ProductStatus\EventListener\ProductFormListener;
use ProductStatus\Model\ProductProductStatusQuery;
use ProductStatus\Model\ProductStatusQuery;
use ProductStatus\Service\ProductStatusService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\Admin;
use Thelia\Model\Product;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

/**
 * The status chosen in the product form of the default-twig back-office, posted to its save action
 * through the kernel, as the General tab of the product edit page sends it. Runs on the test
 * database of the project (`php bin/test-prepare`), never on the shop's.
 */
final class ProductFormListenerTest extends IntegrationTestCase
{
    private FixtureFactory $fixtures;

    protected function setUp(): void
    {
        $databaseName = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($databaseName) || !str_ends_with($databaseName, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $databaseName));
        }

        if (!class_exists(ProductType::class)) {
            self::markTestSkipped('The default-twig back-office is not installed.');
        }

        parent::setUp();

        $this->fixtures = $this->createFixtureFactory();
    }

    public function testTheStatusChosenIsSavedWithTheProduct(): void
    {
        $product = $this->product();

        $response = $this->saveProduct($product, $this->fixtures->admin(), $this->statusId('sale'));

        self::assertSame(302, $response->getStatusCode(), (string) $response->getContent());
        self::assertSame('sale', $this->service()->statusOf($product->getId(), 'en_US')->code);
    }

    public function testTheStatusShownUnchangedWritesNothing(): void
    {
        $product = $this->product();

        $response = $this->saveProduct($product, $this->fixtures->admin(), $this->statusId('normal'));

        self::assertSame(302, $response->getStatusCode(), (string) $response->getContent());
        self::assertSame(0, ProductProductStatusQuery::create()->filterByProductId($product->getId())->count(), 'a product without status keeps no link');
    }

    public function testWithoutTheUpdatePermissionOfTheModuleTheStatusIsLeftAlone(): void
    {
        $product = $this->product();
        $catalogueAdministrator = $this->fixtures->restrictedAdmin([AdminResources::PRODUCT => [AccessManager::VIEW, AccessManager::UPDATE]]);

        $this->saveProduct($product, $catalogueAdministrator, $this->statusId('sale'));

        self::assertSame('normal', $this->service()->statusOf($product->getId(), 'en_US')->code);
    }

    private function saveProduct(Product $product, Admin $admin, int $statusId): Response
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setAdminUser($admin);

        $request = Request::create('/admin/products/save', 'POST');
        $request->setSession($session);
        $request->request->set('product_id', (string) $product->getId());
        $request->request->set(ProductFormListener::PRODUCT_FORM, [
            'id' => (string) $product->getId(),
            'locale' => 'en_US',
            'ref' => $product->getRef(),
            'title' => 'Product '.$product->getId(),
            'default_category' => (string) $product->getDefaultCategoryId(),
            'visible' => '1',
            ProductFormListener::FIELD => (string) $statusId,
            '_token' => $this->csrfToken($request, 'admin.product'),
        ]);

        return $this->handleAsMainRequest($request);
    }

    /**
     * IntegrationTestCase pushes a synthetic request: it is taken off the stack while the
     * kernel handles this one, so that this request is the main request the controller reads.
     */
    private function handleAsMainRequest(Request $request): Response
    {
        $requestStack = $this->requestStack();
        $pushedRequests = [];
        while (null !== $pushedRequest = $requestStack->pop()) {
            $pushedRequests[] = $pushedRequest;
        }

        try {
            return self::$kernel->handle($request);
        } finally {
            while (null !== $requestStack->pop()) {
            }
            foreach (array_reverse($pushedRequests) as $pushedRequest) {
                $requestStack->push($pushedRequest);
            }
        }
    }

    private function requestStack(): RequestStack
    {
        $requestStack = static::getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);

        return $requestStack;
    }

    private function csrfToken(Request $request, string $tokenId): string
    {
        $requestStack = $this->requestStack();
        $tokenManager = static::getContainer()->get('security.csrf.token_manager');
        self::assertInstanceOf(CsrfTokenManagerInterface::class, $tokenManager);

        $requestStack->push($request);
        try {
            return $tokenManager->getToken($tokenId)->getValue();
        } finally {
            $requestStack->pop();
        }
    }

    private function product(): Product
    {
        return $this->fixtures->product($this->fixtures->category(), $this->fixtures->taxRule(), $this->fixtures->currency());
    }

    private function statusId(string $code): int
    {
        return (int) ProductStatusQuery::create()->findOneByCode($code)?->getId();
    }

    private function service(): ProductStatusService
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        return new ProductStatusService($dispatcher);
    }
}
