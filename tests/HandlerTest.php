<?php

declare(strict_types=1);

namespace Duon\Error\Tests;

use DivisionByZeroError;
use Duon\Error\Handler;
use Duon\Error\Tests\Fixtures\TestRenderer;
use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use Throwable;

class HandlerTest extends TestCase
{
	#[TestDox("Don't handle error level 0")]
	public function testErrorHandlerLevel0(): void
	{
		$handler = new Handler($this->factory);

		$this->assertEquals(false, $handler->handleError(0, 'Handler Test'));
		$handler->restoreHandlers();
	}

	#[TestDox("Throw ErrorException when error_reporting level is matched"), WithoutErrorHandler]
	public function testThrowErrorException(): void
	{
		try {
			$handler = new Handler($this->factory);
			$handler->handleError(E_WARNING, 'Handler Test');
		} catch (ErrorException $e) {
			$this->assertStringContainsString('Handler Test', $e->getMessage());

			$handler->restoreHandlers();

			return;
		}

		$this->fail('Exception not thrown');
	}

	#[TestDox("Render default renderer without request")]
	public function testRenderDefaultWithoutRequest(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer());
		$response = $handler->getResponse(new DivisionByZeroError('test message'), null);

		$this->assertEquals('DivisionByZeroError rendered without request test message', (string) $response->getBody());
		$handler->restoreHandlers();
	}

	#[TestDox("Render error without request")]
	public function testRenderErrorWithoutRequest(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer(), ErrorException::class);
		$response = $handler->getResponse(new ErrorException('test message'), null);

		$this->assertEquals('ErrorException rendered without request test message', (string) $response->getBody());
		$handler->restoreHandlers();
	}

	#[TestDox("Render error when no matching exception exists")]
	public function testRenderErrorNotMatching(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer(), ErrorException::class);
		$response = $handler->getResponse(new Exception('test message'), null);

		$this->assertEquals('<h1>500 Internal Server Error</h1>', (string) $response->getBody());
		$handler->restoreHandlers();
	}

	#[TestDox('Add renderer exceptions as array')]
	public function testAddExceptionsAsArray(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer(), [ErrorException::class]);
		$response = $handler->getResponse(new ErrorException('test message'), null);

		$this->assertEquals('ErrorException rendered without request test message', (string) $response->getBody());
		$handler->restoreHandlers();
	}

	#[TestDox("Render error with request")]
	public function testRenderErrorWithRequest(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer(), ErrorException::class);
		$response = $handler->getResponse(new ErrorException('test message'), $this->request());

		$this->assertEquals('ErrorException rendered GET test message', (string) $response->getBody());
		$handler->restoreHandlers();
	}

	#[TestDox("Render error fallback")]
	public function testRenderErrorFallback(): void
	{
		$handler = new Handler($this->factory);
		$response = $handler->getResponse(new ErrorException('test message'), $this->request());

		$this->assertEquals('<h1>500 Internal Server Error</h1>', (string) $response->getBody());
		$this->assertEquals('text/html', (string) $response->getHeaderLine('content-type'));
		$this->assertEquals(500, (string) $response->getStatusCode());
		$handler->restoreHandlers();
	}

	#[TestDox('Handle exception subclasses')]
	public function testResponseWithPHPExceptions(): void
	{
		$handler = new Handler($this->factory);
		$handler->renderer(new TestRenderer(), Throwable::class);
		$response = $handler->getResponse(new ErrorException('test message'), null);

		$this->assertEquals('ErrorException rendered without request test message', (string) $response->getBody());
		$handler->restoreHandlers();
	}
}
