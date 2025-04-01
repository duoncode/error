<?php

declare(strict_types=1);

namespace Duon\Error\Tests;

use DivisionByZeroError;
use Exception;
use Duon\Error\Handler;
use Duon\Error\Tests\Fixtures\TestRenderer;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Throwable;

class MiddlewareTest extends TestCase
{
	#[TestDox('Handled by PSR-15 middleware')]
	public function testHandledByMiddleware(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer(), Throwable::class);
		$response = $handler->process($this->request(), new class implements RequestHandler {
			public function handle(Request $request): Response
			{
				throw new Exception('test message middleware');
			}
		});

		$this->assertEquals('Exception rendered GET test message middleware', (string) $response->getBody());
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
		$handler->renderer(new TestRenderer(), Throwable::class);

		ob_start();
		$handler->emitException(new DivisionByZeroError('division by zero'));
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString('rendered without request division by zero', $output);
	}
}