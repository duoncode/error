<?php

declare(strict_types=1);

namespace Conia\Error\Tests\Fixtures;

use Conia\Error\Renderer;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class TestRenderer implements Renderer
{
    public function render(Throwable $exception, ResponseFactory $factory, ?Request $request, bool $debug): Response
    {
        $response = $factory->createResponse();
        $response->getBody()->write('rendered ' .
            ($request ? $request->getMethod() : 'without request') .
            ' ' .
            $exception->getMessage());

        return $response;
    }
}
