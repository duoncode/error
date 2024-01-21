<?php

declare(strict_types=1);

namespace Conia\Error\Tests;

use Conia\Error\Handler;
use Conia\Error\Tests\Fixtures\TestRenderer;
use DivisionByZeroError;
use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Throwable;

class HandlerTest extends TestCase
{
    #[TestDox("Don't handle error level 0")]
    public function testErrorHandlerLevel0(): void
    {
        $handler = new Handler($this->factory);

        $this->assertEquals(false, $handler->handleError(0, 'Handler Test'));
    }

    #[TestDox("Throw ErrorException when error_reporting level is matched")]
    public function testThrowErrorException(): void
    {
        $this->throws(ErrorException::class, 'Handler Test');

        $handler = new Handler($this->factory);
        $handler->handleError(E_WARNING, 'Handler Test');
    }

    #[TestDox("Don't handle unregistered exception when in debug mode")]
    public function testDontHandleUnregisteredException(): void
    {
        $this->throws(DivisionByZeroError::class, 'test');

        $handler = new Handler($this->factory, debug: true);
        $handler->getResponse(new DivisionByZeroError('test'), null);
    }

    #[TestDox("Render error without request")]
    public function testRenderErrorWithoutRequest(): void
    {
        $handler = new Handler($this->factory);
        $handler->render(ErrorException::class, new TestRenderer());
        $response = $handler->getResponse(new ErrorException('test message'), null);

        $this->assertEquals('rendered without request test message', (string)$response->getBody());
    }

    #[TestDox("Render error when no matching exception exists")]
    public function testRenderErrorNotMatching(): void
    {
        $handler = new Handler($this->factory);
        $handler->render(ErrorException::class, new TestRenderer());
        $response = $handler->getResponse(new Exception('test message'), null);

        $this->assertEquals('<h1>500 Internal Server Error</h1>', (string)$response->getBody());
    }

    #[TestDox('Add renderer exceptions as array')]
    public function testAddExceptionsAsArray(): void
    {
        $handler = new Handler($this->factory);
        $handler->render([ErrorException::class], new TestRenderer());
        $response = $handler->getResponse(new ErrorException('test message'), null);

        $this->assertEquals('rendered without request test message', (string)$response->getBody());
    }

    #[TestDox("Render error with request")]
    public function testRenderErrorWithRequest(): void
    {
        $handler = new Handler($this->factory);
        $handler->render(ErrorException::class, new TestRenderer());
        $response = $handler->getResponse(new ErrorException('test message'), $this->request());

        $this->assertEquals('rendered GET test message', (string)$response->getBody());
    }

    #[TestDox("Render error fallback")]
    public function testRenderErrorFallback(): void
    {
        $handler = new Handler($this->factory);
        $response = $handler->getResponse(new ErrorException('test message'), $this->request());

        $this->assertEquals('<h1>500 Internal Server Error</h1>', (string)$response->getBody());
        $this->assertEquals('text/html', (string)$response->getHeaderLine('content-type'));
        $this->assertEquals(500, (string)$response->getStatusCode());
    }

    #[TestDox('Handle exception subclasses')]
    public function testResponseWithPHPExceptions(): void
    {
        $handler = new Handler($this->factory);
        $handler->render(Throwable::class, new TestRenderer());
        $response = $handler->getResponse(new ErrorException('test message'), null);

        $this->assertEquals('rendered without request test message', (string)$response->getBody());
    }

    #[TestDox('Handled by PSR-15 middleware')]
    public function testHandledByMiddleware(): void
    {
        $handler = new Handler($this->factory);
        $handler->render(Throwable::class, new TestRenderer());
        $response = $handler->process($this->request(), new class () implements RequestHandler {
            public function handle(Request $request): Response
            {
                throw new Exception('test message middleware');
            }
        });

        $this->assertEquals('rendered GET test message middleware', (string)$response->getBody());
    }

    #[TestDox('Emit PHP exception unrelated to middleware')]
    public function testEmitPHPExceptions(): void
    {
        $handler = new Handler($this->factory);

        ob_start();
        $handler->emitException(new DivisionByZeroError('division by zero'));
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('<h1>500 Internal Server Error</h1>', $output);
    }

    #[TestDox('Emit PHP exception unrelated to middleware with renderer')]
    public function testEmitPHPExceptionsWithRenderer(): void
    {
        $handler = new Handler($this->factory);
        $handler->render(Throwable::class, new TestRenderer());

        ob_start();
        $handler->emitException(new DivisionByZeroError('division by zero'));
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString('rendered without request division by zero', $output);
    }
}
