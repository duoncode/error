<?php

declare(strict_types=1);

namespace Duon\Error\Tests;

use DivisionByZeroError;
use Duon\Error\Handler;
use Duon\Error\Tests\Fixtures\TestDebugHandler;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

class DebugTest extends TestCase
{
	#[TestDox("Don't handle unregistered exception when in debug mode"), WithoutErrorHandler]
	public function testDontHandleUnregisteredException(): void
	{
		try {
			$handler = new Handler($this->factory, debug: true);
			$handler->getResponse(new DivisionByZeroError('test'), null);
		} catch (DivisionByZeroError $e) {
			$this->assertStringContainsString('test', $e->getMessage());

			$handler->restoreHandlers();

			return;
		}

		$this->fail('Exception not thrown');
	}

	#[TestDox("Handle unregistered exception with debug handler")]
	public function testErrorHandlerLevel0(): void
	{
		$handler = new Handler($this->factory, debug: true);
		$handler->debugHandler(new TestDebugHandler());
		$response = $handler->getResponse(new DivisionByZeroError('test'), null);

		$this->assertEquals('DivisionByZeroError test', (string) $response->getBody());
		$handler->restoreHandlers();
	}

	#[TestDox("Print error_log in debug mode")]
	public function testErrorlog(): void
	{
		$handler = new Handler($this->factory, debug: true);
		$handler->debugHandler(new TestDebugHandler());

		// TODO: Should also test the output of error_log.
		//       The code runs but is not tested for correctness.
		ob_start();
		$handler->emitException(new DivisionByZeroError('test'), null);
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('DivisionByZeroError test', $output);
		$handler->restoreHandlers();
	}
}
