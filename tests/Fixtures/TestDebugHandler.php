<?php

declare(strict_types=1);

namespace Conia\Error\Tests\Fixtures;

use Conia\Error\DebugHandler;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class TestDebugHandler implements DebugHandler
{
    public function handle(Throwable $exception, ResponseFactory $factory): Response
    {
        $response = $factory->createResponse();
        $response->getBody()->write($exception::class . ' ' . $exception->getMessage());

        return $response;
    }
}
