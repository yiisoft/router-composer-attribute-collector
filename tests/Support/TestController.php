<?php

declare(strict_types=1);

namespace Yiisoft\Router\ComposerAttributeCollector\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\Attribute\Get;
use Yiisoft\Router\Attribute\Route;

#[Route(methods: ['GET'], pattern: '/test')]
final class TestController
{
    #[Get('/')]
    public function index(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write('Test');

        return $response->withStatus(200);
    }
}
