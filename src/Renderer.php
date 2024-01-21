<?php

declare(strict_types=1);

namespace Conia\Error;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

interface Renderer
{
    public function render(Throwable $exception, Response $response, ?Request $request, bool $debug): Response;
}
