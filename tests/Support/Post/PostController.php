<?php

declare(strict_types=1);

namespace Yiisoft\Router\ComposerAttributeCollector\Tests\Support\Post;

use Yiisoft\Router\Attribute\Delete;
use Yiisoft\Router\Attribute\Get;
use Yiisoft\Router\Attribute\Post;
use Yiisoft\Router\Attribute\Put;
use Yiisoft\Router\Group;

#[Group(prefix: '/post')]
final class PostController
{
    #[Get('')]
    public function list()
    {
    }

    #[Get('/{slug}')]
    public function view()
    {
    }

    #[Put('/{slug}')]
    public function edit()
    {
    }

    #[Post('/')]
    public function create()
    {
    }

    #[Delete('/{slug}')]
    public function delete()
    {
    }
}
