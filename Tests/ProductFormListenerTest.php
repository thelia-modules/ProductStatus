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
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Model\Admin;
use Thelia\Model\Product;
use Thelia\Model\ProductQuery;
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

    public function testWithoutTheUpdatePermissionOfTheModuleTheFormCarryingTheStatusIsRefused(): void
    {
        $product = $this->product();
        $titleBefore = $product->setLocale('en_US')->getTitle();
        $catalogueAdministrator = $this->fixtures->restrictedAdmin([AdminResources::PRODUCT => [AccessManager::VIEW, AccessManager::UPDATE]]);

        $session = new Session(new MockArraySessionStorage());

        $response = $this->saveProduct($product, $catalogueAdministrator, $this->statusId('sale'), $session);

        self::assertSame(302, $response->getStatusCode(), (string) $response->getContent());
        self::assertNotEmpty($session->getFlashBag()->peek('danger'), 'the form refused for its extra field');
        self::assertSame('normal', $this->service()->statusOf($product->getId(), 'en_US')->code);
        self::assertSame($titleBefore, ProductQuery::create()->findPk($product->getId())?->setLocale('en_US')->getTitle(), 'the product is not saved either');
    }

    public function testAStatusDeletedSinceThePageWasDisplayedRefusesTheForm(): void
    {
        $product = $this->product();
        $session = new Session(new MockArraySessionStorage());

        $response = $this->saveProduct($product, $this->fixtures->admin(), $this->unknownStatusId(), $session);

        self::assertSame(302, $response->getStatusCode(), (string) $response->getContent());
        self::assertNotEmpty($session->getFlashBag()->peek('danger'), 'the choice is no longer valid');
        self::assertSame(0, ProductProductStatusQuery::create()->filterByProductId($product->getId())->count());
    }

    public function testWithoutTheUpdatePermissionOfTheModuleTheListenerWritesNothing(): void
    {
        $product = $this->product();
        $this->signIn($this->fixtures->restrictedAdmin([AdminResources::PRODUCT => [AccessManager::VIEW, AccessManager::UPDATE]]));

        $this->listener()->saveStatus($this->updateEvent($product, $this->statusId('sale')));

        self::assertSame('normal', $this->service()->statusOf($product->getId(), 'en_US')->code);
    }

    public function testAStatusDeletedBetweenTheValidationAndTheWriteLeavesAWarningAndNoException(): void
    {
        $product = $this->product();
        $session = $this->signIn($this->fixtures->admin());
        $this->listener()->saveStatus($this->updateEvent($product, $this->unknownStatusId()));

        self::assertCount(1, $session->getFlashBag()->peek('warning'));
        self::assertSame('normal', $this->service()->statusOf($product->getId(), 'en_US')->code);
    }

    private function signIn(Admin $admin): Session
    {
        $session = $this->requestStack()->getMainRequest()?->getSession();
        self::assertInstanceOf(Session::class, $session);
        $session->setAdminUser($admin);

        return $session;
    }

    private function updateEvent(Product $product, int $statusId): ProductUpdateEvent
    {
        $event = (new ProductUpdateEvent($product->getId()))->setLocale('en_US');
        $event->setProduct($product);
        // As the legacy form bridge of the back-office sets a field without a setter on the event.
        $event->__set(ProductFormListener::FIELD, (string) $statusId);

        return $event;
    }

    private function listener(): ProductFormListener
    {
        $listener = static::getContainer()->get(ProductFormListener::class);
        self::assertInstanceOf(ProductFormListener::class, $listener);

        return $listener;
    }

    private function saveProduct(Product $product, Admin $admin, int $statusId, Session $session = new Session(new MockArraySessionStorage())): Response
    {
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
        $pushedRequests = $this->popAll($requestStack);

        try {
            return self::$kernel->handle($request);
        } finally {
            $this->popAll($requestStack);
            foreach (array_reverse($pushedRequests) as $pushedRequest) {
                $requestStack->push($pushedRequest);
            }
        }
    }

    /**
     * @return list<Request>
     */
    private function popAll(RequestStack $requestStack): array
    {
        $poppedRequests = [];
        while (null !== $poppedRequest = $requestStack->pop()) {
            $poppedRequests[] = $poppedRequest;
        }

        return $poppedRequests;
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

    private function unknownStatusId(): int
    {
        return (int) ProductStatusQuery::create()->orderById(Criteria::DESC)->findOne()?->getId() + 1000;
    }

    private function service(): ProductStatusService
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        return new ProductStatusService($dispatcher);
    }
}
