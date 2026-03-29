<?php

declare(strict_types=1);

namespace Yiisoft\Router\ComposerAttributeCollector\Tests;

use Closure;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\Collection;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Attribute\Delete;
use Yiisoft\Router\Attribute\Get;
use Yiisoft\Router\Attribute\Post;
use Yiisoft\Router\Attribute\Put;
use Yiisoft\Router\ComposerAttributeCollector\AttributeRoutesProvider;
use Yiisoft\Router\ComposerAttributeCollector\Tests\Support\Post\PostController;
use Yiisoft\Router\ComposerAttributeCollector\Tests\Support\TestController;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

final class AttributeRoutesProviderTest extends TestCase
{
    private ?Closure $previousProvider = null;

    protected function tearDown(): void
    {
        if ($this->previousProvider !== null) {
            Attributes::with($this->previousProvider);
        }

        parent::tearDown();
    }

    public function testReturnsEmptyArrayWhenNoAttributesRegistered(): void
    {
        $this->setUpCollection([], []);

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertSame([], $routes);
    }

    public function testReturnsUngroupedRouteFromMethodAttribute(): void
    {
        $this->setUpCollection(
            targetClasses: [],
            targetMethods: [
                Get::class => [
                    [['/'], TestController::class, 'index'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);

        $route = $routes[0];
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('/', $route->getData('pattern'));
        $this->assertSame(['GET'], $route->getData('methods'));

        $middlewares = $route->getData('enabledMiddlewares');
        $this->assertSame([[TestController::class, 'index']], $middlewares);
    }

    public function testReturnsMultipleUngroupedRoutes(): void
    {
        $this->setUpCollection(
            targetClasses: [],
            targetMethods: [
                Get::class => [
                    [['/'], TestController::class, 'index'],
                ],
                Post::class => [
                    [['/create'], TestController::class, 'index'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(2, $routes);

        $patterns = array_map(
            static fn(Group|Route $route): string => $route->getData('pattern'),
            $routes,
        );
        $this->assertContains('/', $patterns);
        $this->assertContains('/create', $patterns);
    }

    public function testClassLevelRouteAttributeSetsClassAsAction(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Route::class => [
                    [[['GET'], '/test'], TestController::class],
                ],
            ],
            targetMethods: [],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);

        $route = $routes[0];
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('/test', $route->getData('pattern'));
        $this->assertSame(['GET'], $route->getData('methods'));
    }

    public function testReturnsGroupedRoutesWithGroupAttribute(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [
                Get::class => [
                    [[''], PostController::class, 'list'],
                    [['/{slug}'], PostController::class, 'view'],
                ],
                Put::class => [
                    [['/{slug}'], PostController::class, 'edit'],
                ],
                Post::class => [
                    [['/'], PostController::class, 'create'],
                ],
                Delete::class => [
                    [['/{slug}'], PostController::class, 'delete'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);

        $group = $routes[0];
        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame('/post', $group->getData('prefix'));

        $groupRoutes = $group->getData('routes');
        $this->assertCount(5, $groupRoutes);

        $patterns = [];
        $methods = [];
        foreach ($groupRoutes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $patterns[] = $route->getData('pattern');
            $methods[] = $route->getData('methods');
        }

        $this->assertContains('', $patterns);
        $this->assertContains('/{slug}', $patterns);
        $this->assertContains('/', $patterns);
        $this->assertContains(['GET'], $methods);
        $this->assertContains(['PUT'], $methods);
        $this->assertContains(['POST'], $methods);
        $this->assertContains(['DELETE'], $methods);
    }

    public function testMixesGroupedAndUngroupedRoutes(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [
                Get::class => [
                    [['/'], TestController::class, 'index'],
                    [[''], PostController::class, 'list'],
                ],
                Post::class => [
                    [['/'], PostController::class, 'create'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(2, $routes);

        $routeTypes = array_map(
            static fn(Route|Group $route): string => $route instanceof Group ? 'group' : 'route',
            $routes,
        );

        $this->assertContains('group', $routeTypes);
        $this->assertContains('route', $routeTypes);
    }

    public function testGroupedRouteActionsAreSetCorrectly(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [
                Get::class => [
                    [[''], PostController::class, 'list'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $group = $routes[0];
        $this->assertInstanceOf(Group::class, $group);

        $groupRoutes = $group->getData('routes');
        $this->assertCount(1, $groupRoutes);

        $route = $groupRoutes[0];
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('', $route->getData('pattern'));
        $this->assertSame(['GET'], $route->getData('methods'));

        $middlewares = $route->getData('enabledMiddlewares');
        $this->assertSame([[PostController::class, 'list']], $middlewares);
    }

    public function testClassLevelRouteAndGroupCoexist(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Route::class => [
                    [[['GET'], '/test'], TestController::class],
                ],
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [
                Get::class => [
                    [[''], PostController::class, 'list'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(2, $routes);

        $hasGroup = false;
        $hasClassRoute = false;
        foreach ($routes as $route) {
            if ($route instanceof Group) {
                $hasGroup = true;
                $this->assertSame('/post', $route->getData('prefix'));
            } elseif ($route instanceof Route) {
                $hasClassRoute = true;
                $this->assertSame('/test', $route->getData('pattern'));
            }
        }

        $this->assertTrue($hasGroup);
        $this->assertTrue($hasClassRoute);
    }

    public function testReflectionCacheIsUsedForSameClass(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [
                Get::class => [
                    [[''], PostController::class, 'list'],
                    [['/{slug}'], PostController::class, 'view'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);

        $group = $routes[0];
        $this->assertInstanceOf(Group::class, $group);

        $groupRoutes = $group->getData('routes');
        $this->assertCount(2, $groupRoutes);

        $this->assertSame([[PostController::class, 'list']], $groupRoutes[0]->getData('enabledMiddlewares'));
        $this->assertSame([[PostController::class, 'view']], $groupRoutes[1]->getData('enabledMiddlewares'));
    }

    public function testUnrelatedClassAttributeIsIgnored(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Get::class => [
                    [['/ignored'], TestController::class],
                ],
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [
                Get::class => [
                    [[''], PostController::class, 'list'],
                ],
            ],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertInstanceOf(Group::class, $routes[0]);
    }

    public function testGroupWithoutMatchingRoutesIsNotIncluded(): void
    {
        $this->setUpCollection(
            targetClasses: [
                Group::class => [
                    [['/post'], PostController::class],
                ],
            ],
            targetMethods: [],
        );

        $provider = new AttributeRoutesProvider();
        $routes = $provider->getRoutes();

        $this->assertSame([], $routes);
    }

    /**
     * @param array<class-string, list<array{array, class-string}>> $targetClasses
     * @param array<class-string, list<array{array, class-string, string}>> $targetMethods
     */
    private function setUpCollection(array $targetClasses, array $targetMethods): void
    {
        $this->previousProvider = Attributes::with(
            static fn(): Collection => new Collection(
                targetClasses: $targetClasses,
                targetMethods: $targetMethods,
                targetProperties: [],
                targetParameters: [],
            ),
        );
    }
}
